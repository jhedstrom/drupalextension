<?php

declare(strict_types=1);

namespace Drupal\DrupalExtension\Context;

use Drupal\Component\Utility\Random;
use Behat\Hook\AfterScenario;
use Drupal\Driver\Capability\CacheCapabilityInterface;
use Drupal\Driver\Capability\ContentCapabilityInterface;
use Drupal\Driver\Capability\LanguageCapabilityInterface;
use Drupal\Driver\Capability\RoleCapabilityInterface;
use Drupal\Driver\Capability\UserCapabilityInterface;
use Drupal\Driver\DrupalDriver;
use Drupal\Driver\Entity\EntityStubInterface;
use Drupal\DrupalExtension\Hook\Attribute\BeforeNodeCreate;
use Drupal\taxonomy\Entity\Vocabulary;
use Behat\MinkExtension\Context\RawMinkContext;
use Behat\Testwork\Hook\HookDispatcher;
use Behat\Behat\Context\Environment\InitializedContextEnvironment;

use Drupal\DrupalDriverManagerInterface;
use Drupal\DrupalExtension\DrupalParametersTrait;
use Drupal\DrupalExtension\Manager\DrupalAuthenticationManagerInterface;
use Drupal\DrupalExtension\Manager\DrupalUserManagerInterface;
use Drupal\DrupalExtension\Parser\LegacyEntityFieldsParser;
use Drupal\DrupalExtension\Parser\ParserInterface;

use Drupal\DrupalExtension\Hook\Scope\AfterLanguageCreateScope;
use Drupal\DrupalExtension\Hook\Scope\AfterNodeCreateScope;
use Drupal\DrupalExtension\Hook\Scope\AfterTermCreateScope;
use Drupal\DrupalExtension\Hook\Scope\AfterUserCreateScope;
use Drupal\DrupalExtension\Hook\Scope\BeforeLanguageCreateScope;
use Drupal\DrupalExtension\Hook\Scope\BeforeNodeCreateScope;
use Drupal\DrupalExtension\Hook\Scope\BeforeTermCreateScope;
use Drupal\DrupalExtension\Hook\Scope\BeforeUserCreateScope;
use Drupal\DrupalExtension\Manager\FastLogoutInterface;

/**
 * Provides the raw functionality for interacting with Drupal.
 */
class RawDrupalContext extends RawMinkContext implements DrupalAwareInterface {

  use DrupalParametersTrait;

  /**
   * Drupal driver manager.
   */
  private ?DrupalDriverManagerInterface $drupalDriverManager = NULL;

  /**
   * Event dispatcher object.
   *
   * @var \Behat\Testwork\Hook\HookDispatcher
   */
  protected $dispatcher;

  /**
   * Drupal authentication manager.
   *
   * @var \Drupal\DrupalExtension\Manager\DrupalAuthenticationManagerInterface
   */
  protected $authenticationManager;

  /**
   * Drupal user manager.
   *
   * @var \Drupal\DrupalExtension\Manager\DrupalUserManagerInterface
   */
  protected $userManager;

  /**
   * Tracks every entity stub created during a scenario for cleanup.
   *
   * Users are tracked separately via the user manager because they need
   * lookup-by-name. Everything else (nodes, terms, languages, generic
   * entities) lives here and is removed in reverse order so dependent
   * entities come down before their dependencies.
   *
   * @var array<int, \Drupal\Driver\Entity\EntityStubInterface>
   */
  protected array $createdStubs = [];

  /**
   * Field-value parser, lazily instantiated on first use.
   */
  protected ?ParserInterface $fieldParser = NULL;

  /**
   * Keep track of any roles that are created so they can easily be removed.
   *
   * @var array<int, string>
   */
  protected array $roles = [];

  /**
   * {@inheritdoc}
   */
  public function setDrupal(DrupalDriverManagerInterface $drupal): void {
    $this->drupalDriverManager = $drupal;
  }

  /**
   * {@inheritdoc}
   */
  public function getDrupal(): ?DrupalDriverManagerInterface {
    return $this->drupalDriverManager;
  }

  /**
   * {@inheritdoc}
   */
  public function setUserManager(DrupalUserManagerInterface $userManager): void {
    $this->userManager = $userManager;
  }

  /**
   * {@inheritdoc}
   */
  public function getUserManager() {
    return $this->userManager;
  }

  /**
   * {@inheritdoc}
   */
  public function setAuthenticationManager(DrupalAuthenticationManagerInterface $authenticationManager): void {
    $this->authenticationManager = $authenticationManager;
  }

  /**
   * {@inheritdoc}
   */
  public function getAuthenticationManager() {
    return $this->authenticationManager;
  }

  /**
   * {@inheritdoc}
   */
  public function setDispatcher(HookDispatcher $dispatcher): void {
    $this->dispatcher = $dispatcher;
  }

  /**
   * Get active Drupal Driver.
   *
   * @return \Drupal\Driver\DriverInterface
   *   The active driver instance.
   */
  public function getDriver(?string $name = NULL) {
    return $this->getDrupal()->getDriver($name);
  }

  /**
   * Get driver's random generator.
   */
  public function getRandom(): Random {
    return $this->getDriver()->getRandom();
  }

  /**
   * Massage node values to match the expectations on different Drupal versions.
   */
  #[BeforeNodeCreate]
  public static function alterNodeParameters(BeforeNodeCreateScope $scope): void {
    $stub = $scope->getStub();

    // Convert string dates on timestamp fields when the in-process Drupal
    // driver is active. Blackbox and Drush drivers route around this entity
    // pipeline entirely, so the conversion is only meaningful for the API
    // path.
    $context = $scope->getContext();

    if (!$context instanceof DrupalAwareInterface) {
      return;
    }

    if (!$context->getDrupal()->getDriver() instanceof DrupalDriver) {
      return;
    }

    foreach (['changed', 'created', 'revision_timestamp'] as $field) {
      $value = $stub->getValue($field);

      if ($value !== NULL && $value !== '' && !is_numeric($value)) {
        $stub->setValue($field, strtotime((string) $value));
      }
    }
  }

  /**
   * Remove every entity created during the scenario.
   *
   * Walks 'createdStubs' in reverse order so dependent entities (e.g. nodes
   * referencing terms) come down before the entities they reference.
   */
  #[AfterScenario]
  public function cleanEntities(): void {
    if ($this->createdStubs === []) {
      return;
    }

    $driver = $this->getDriver();

    foreach (array_reverse($this->createdStubs) as $stub) {
      $this->deleteStub($stub, $driver);
    }

    $this->createdStubs = [];
  }

  /**
   * Routes a stub to the right per-type driver delete method.
   */
  protected function deleteStub(EntityStubInterface $stub, object $driver): void {
    $type = $stub->getEntityType();

    if (in_array($type, ['language', 'configurable_language'], TRUE)) {
      if ($driver instanceof LanguageCapabilityInterface) {
        $driver->languageDelete($stub);
      }

      return;
    }

    if (!$driver instanceof ContentCapabilityInterface) {
      return;
    }

    match ($type) {
      'node' => $driver->nodeDelete($stub),
      'taxonomy_term' => $driver->termDelete($stub),
      default => $driver->entityDelete($stub),
    };
  }

  /**
   * Remove any created users.
   */
  #[AfterScenario]
  public function cleanUsers(): void {
    $driver = $this->getDriver();

    if ($this->userManager->hasUsers() && $driver instanceof UserCapabilityInterface) {
      foreach ($this->userManager->getUsers() as $user) {
        $driver->userDelete($user);
      }

      $this->processDriverBatch($driver);
      $this->userManager->clearUsers();
    }

    // Always reset auth state, even if no users were created during the
    // scenario. A scenario may log in as a pre-existing user without calling
    // userCreate(), leaving stale session state for the next scenario.
    if ($this->getAuthenticationManager() instanceof FastLogoutInterface) {
      $this->logout(TRUE);
    }
    elseif (!$this->userManager->currentUserIsAnonymous()) {
      $this->logout();
    }
  }

  /**
   * Drains pending Drupal batch operations on drivers that support batches.
   *
   * 'processBatch()' lives on the concrete 'DrupalDriver' / 'DrushDriver'
   * classes but is not part of any 'Capability' interface, so guard the
   * call with an explicit method existence check rather than a type
   * narrowing.
   */
  protected function processDriverBatch(object $driver): void {
    if (method_exists($driver, 'processBatch')) {
      $driver->processBatch();
    }
  }

  /**
   * Remove any created roles.
   */
  #[AfterScenario]
  public function cleanRoles(): void {
    if ($this->roles === []) {
      return;
    }

    $driver = $this->getDriver();

    if (!$driver instanceof RoleCapabilityInterface) {
      return;
    }

    foreach ($this->roles as $role) {
      $driver->roleDelete($role);
    }

    $this->roles = [];
  }

  /**
   * Clear static caches.
   */
  #[AfterScenario('@api')]
  public function clearStaticCaches(): void {
    $driver = $this->getDriver();

    if ($driver instanceof CacheCapabilityInterface) {
      $driver->cacheClearStatic();
    }
  }

  /**
   * Dispatch scope hooks.
   *
   * @param class-string<\Behat\Testwork\Hook\Scope\HookScope> $scopeClass
   *   The fully-qualified scope class name.
   * @param \Drupal\Driver\Entity\EntityStubInterface $stub
   *   The entity stub flowing through the create pipeline.
   */
  protected function dispatchHooks(string $scopeClass, EntityStubInterface $stub): void {
    $scope = new $scopeClass($this->getDrupal()->getEnvironment(), $this, $stub);
    $call_results = $this->dispatcher->dispatchScopeHooks($scope);

    // The dispatcher suppresses exceptions, throw them here if there are any.
    foreach ($call_results as $call_result) {
      if ($call_result->hasException()) {
        $exception = $call_result->getException();
        throw $exception;
      }
    }
  }

  /**
   * Create a node.
   *
   * @param \Drupal\Driver\Entity\EntityStubInterface $stub
   *   The node stub.
   *
   * @return \Drupal\Driver\Entity\EntityStubInterface
   *   The same stub, now flagged as saved.
   */
  public function nodeCreate(EntityStubInterface $stub): EntityStubInterface {
    $this->dispatchHooks(BeforeNodeCreateScope::class, $stub);
    $this->parseEntityFields($stub, ['author']);

    $driver = $this->getContentDriver();

    $scalars = $this->captureScalarBaseFields($stub);
    $driver->nodeCreate($stub);
    $this->restoreScalarBaseFields($stub, $scalars);

    $this->dispatchHooks(AfterNodeCreateScope::class, $stub);
    $this->createdStubs[] = $stub;

    return $stub;
  }

  /**
   * Parses field values from Behat table cells into Drupal's field format.
   *
   * Only configurable fields (those for which the driver's isField() returns
   * TRUE) are parsed. Base properties like "title" or "status" pass through
   * unchanged.
   *
   * Single value:
   * @code
   * | title       | field_color |
   * | My article  | Red         |
   * @endcode
   * Result: field_color = ['Red'].
   *
   * Multiple values (comma-separated):
   * @code
   * | field_tags        |
   * | Sports, Politics  |
   * @endcode
   * Result: field_tags = ['Sports', 'Politics'].
   * Wrap in double quotes to include a literal comma: "a value, with comma".
   *
   * Compound columns using ' - ' separator (e.g. link field with uri + title):
   * @code
   * | field_link                        |
   * | http://example.com - Example site |
   * @endcode
   * Result: field_link = [['http://example.com', 'Example site']].
   *
   * Named compound columns using inline 'key: value' syntax:
   * @code
   * | field_link                                    |
   * | uri: http://example.com - title: Example site |
   * @endcode
   * Result: field_link = [
   *   ['uri' => 'http://example.com', 'title' => 'Example site']
   * ].
   *
   * Multi-value compound (comma separates each value set):
   * @code
   * | field_link                                                 |
   * | uri: /about - title: About, uri: /contact - title: Contact |
   * @endcode
   * Result: field_link = [
   *   ['uri' => '/about', 'title' => 'About'],
   *   ['uri' => '/contact', 'title' => 'Contact'],
   * ].
   *
   * Multicolumn table headers using 'field:column' and ':column' syntax
   * (useful when compound values contain commas or separators):
   * @code
   * | field_link:uri          | :title       |
   * | http://example.com      | Example site |
   * @endcode
   * Result: field_link = [
   *   ['uri' => 'http://example.com', 'title' => 'Example site']
   * ].
   *
   * Multi-value multicolumn (comma-separated within each cell):
   * @code
   * | field_link:uri          | :title              |
   * | /about, /contact        | About, Contact      |
   * @endcode
   * Result: field_link = [
   *   ['uri' => '/about', 'title' => 'About'],
   *   ['uri' => '/contact', 'title' => 'Contact'],
   * ].
   *
   * Blank values remove the field from the stub so Drupal applies its
   * default.
   *
   * @param \Drupal\Driver\Entity\EntityStubInterface $stub
   *   The entity stub. Recognised field values are replaced in-place with
   *   structured arrays; other values are left unchanged.
   * @param string[] $ignored_properties
   *   Property names to skip during validation. Use this for properties that
   *   are consumed by the driver (e.g. 'role', 'vocabulary_machine_name') but
   *   are not real Drupal fields.
   *
   * @throws \RuntimeException
   *   Thrown when a column continuation (':column') appears without a
   *   preceding 'field:column' header, or when a property is neither a
   *   configurable field, a base field, nor in the ignored list.
   */
  public function parseEntityFields(EntityStubInterface $stub, array $ignored_properties = []): void {
    $driver = $this->getDriver();

    if (!$driver instanceof DrupalDriver) {
      throw new \RuntimeException(sprintf('The active Drupal driver "%s" does not support field inspection.', $driver::class));
    }

    $entity_type = $stub->getEntityType();
    $classifier = $driver->getCore()->getFieldClassifier();
    $parser = $this->getFieldParser();

    $multicolumn_field = '';
    $multicolumn_column = '';
    $multicolumn_fields = [];
    $values = $stub->getValues();
    $parsed = [];

    foreach ($values as $field => $field_value) {
      $field = (string) $field;

      // Reset the multicolumn field if the field name does not have a column.
      if (!str_contains($field, ':')) {
        $multicolumn_field = '';
        $multicolumn_column = '';
      }
      elseif (str_contains(substr($field, 1), ':')) {
        // Start tracking a new multicolumn field if the field name contains a
        // ':' which is preceded by at least 1 character.
        [$multicolumn_field, $multicolumn_column] = explode(':', $field);
      }
      elseif (empty($multicolumn_field)) {
        // If a field name starts with a ':' but we are not yet tracking a
        // multicolumn field we don't know to which field this belongs.
        throw new \RuntimeException('Field name missing for ' . $field);
      }
      else {
        // Update the column name if the field name starts with a ':' and we are
        // already tracking a multicolumn field.
        $multicolumn_column = substr($field, 1);
      }

      $is_multicolumn = $multicolumn_field !== '' && $multicolumn_column !== '';
      $field_name = $multicolumn_field !== '' ? $multicolumn_field : $field;

      if ($classifier->fieldIsConfigurable($entity_type, $field_name)) {
        $records = $parser->parse((string) $field_value, $is_multicolumn);

        if ($is_multicolumn) {
          foreach ($records as $key => $columns) {
            $multicolumn_fields[$multicolumn_field][$key][$multicolumn_column] = $columns;
          }
        }
        elseif ($field_value === '' || $field_value === NULL) {
          // Don't specify any value if the step author has left it blank.
          unset($parsed[$field_name]);
        }
        else {
          $parsed[$field_name] = $records;
        }
      }
      else {
        // The v2 'fieldIsBase()' predicate returned TRUE for any field in
        // 'getBaseFieldDefinitions()'. The v3 classifier splits that set
        // across F1-F4 (standard, computed read-only, computed writable,
        // custom storage), so the OR replaces the single v2 check and keeps
        // computed/custom-storage base fields like 'moderation_state' from
        // tripping the unknown-field guard.
        $is_base_field = $classifier->fieldIsBaseStandard($entity_type, $field_name)
          || $classifier->fieldIsBaseComputedReadOnly($entity_type, $field_name)
          || $classifier->fieldIsBaseComputedWritable($entity_type, $field_name)
          || $classifier->fieldIsBaseCustomStorage($entity_type, $field_name);

        if (!$is_base_field && !in_array($field_name, $ignored_properties, TRUE)) {
          throw new \RuntimeException(sprintf('Field "%s" does not exist on entity type "%s".', $field_name, $entity_type));
        }

        $parsed[$field] = $field_value;
      }
    }

    // Add the multicolumn fields. Each entry in 'multicolumn_fields' is only
    // set when there is at least one non-blank cell.
    foreach ($multicolumn_fields as $field_name => $columns) {
      $parsed[$field_name] = $columns;
    }

    $stub->setValues($parsed);
  }

  /**
   * Returns the active field-value parser, instantiating it on first use.
   *
   * Override in a subclass or replace via the protected setter to swap in
   * a different parser implementation (e.g. the v6 modern parser, when it
   * lands in 6.0).
   */
  protected function getFieldParser(): ParserInterface {
    return $this->fieldParser ??= new LegacyEntityFieldsParser();
  }

  /**
   * Create a user.
   *
   * @param \Drupal\Driver\Entity\EntityStubInterface $stub
   *   The user stub.
   *
   * @return \Drupal\Driver\Entity\EntityStubInterface
   *   The same stub, now flagged as saved.
   */
  public function userCreate(EntityStubInterface $stub): EntityStubInterface {
    $this->dispatchHooks(BeforeUserCreateScope::class, $stub);
    $this->parseEntityFields($stub, ['role']);

    $driver = $this->getDriver();

    if (!$driver instanceof UserCapabilityInterface) {
      throw new \RuntimeException(sprintf('The active Drupal driver "%s" does not support user creation.', $driver::class));
    }

    $scalars = $this->captureScalarBaseFields($stub);
    $driver->userCreate($stub);
    $this->restoreScalarBaseFields($stub, $scalars);

    $this->dispatchHooks(AfterUserCreateScope::class, $stub);
    $this->userManager->addUser($stub);

    return $stub;
  }

  /**
   * Create a term.
   *
   * @param \Drupal\Driver\Entity\EntityStubInterface $stub
   *   The term stub.
   *
   * @return \Drupal\Driver\Entity\EntityStubInterface
   *   The same stub, now flagged as saved.
   */
  public function termCreate(EntityStubInterface $stub): EntityStubInterface {
    // The 3.x DrupalDriver only loads vocabularies by machine name. Allow
    // the Gherkin author to pass either the machine name or the human
    // label by resolving the label to a machine name when the literal
    // string does not match a vocabulary id. Resolution is best-effort -
    // the driver throws a clearer error than we could when the lookup
    // ultimately fails.
    $vocabulary = $stub->getValue('vocabulary_machine_name');

    if (!empty($vocabulary)) {
      $stub->setValue('vocabulary_machine_name', $this->resolveVocabularyMachineName((string) $vocabulary));
    }

    // The 3.x DrupalDriver resolves a 'parent' property as a term name
    // against the configured vocabulary and throws if it does not exist;
    // pass the name through unchanged. An empty 'parent' must be removed
    // so the field-handler pipeline does not try to expand the empty
    // string as an entity reference.
    if ($stub->hasValue('parent') && empty($stub->getValue('parent'))) {
      $stub->removeValue('parent');
    }

    $this->dispatchHooks(BeforeTermCreateScope::class, $stub);
    $this->parseEntityFields($stub, ['vocabulary_machine_name']);

    $driver = $this->getContentDriver();

    $scalars = $this->captureScalarBaseFields($stub);
    $driver->termCreate($stub);
    $this->restoreScalarBaseFields($stub, $scalars);

    $this->dispatchHooks(AfterTermCreateScope::class, $stub);
    $this->createdStubs[] = $stub;

    return $stub;
  }

  /**
   * Resolves a vocabulary identifier to its machine name.
   *
   * Accepts either the machine name (returned as-is) or the human label
   * (looked up via the vocabulary storage). Falls back to the original
   * value when no label match exists, leaving the driver to surface a
   * not-found error.
   */
  protected function resolveVocabularyMachineName(string $identifier): string {
    if (!class_exists(Vocabulary::class) || Vocabulary::load($identifier) instanceof Vocabulary) {
      return $identifier;
    }

    foreach (Vocabulary::loadMultiple() as $vocabulary) {
      if ($vocabulary->label() === $identifier) {
        return $vocabulary->id();
      }
    }

    return $identifier;
  }

  /**
   * Captures scalar values on an entity stub.
   *
   * The 3.x DrupalDriver runs base fields through the field-handler
   * pipeline during create, which casts scalar values such as 'title',
   * 'name', 'mail' or 'pass' to single-element arrays. Most drupalextension
   * downstream code (user manager indexing, login flow, stub matching)
   * expects scalars, so callers snapshot the scalars before the driver
   * call and restore them after.
   *
   * @param \Drupal\Driver\Entity\EntityStubInterface $stub
   *   The entity stub to inspect.
   *
   * @return array<string, scalar>
   *   The scalar values keyed by name.
   */
  protected function captureScalarBaseFields(EntityStubInterface $stub): array {
    return array_filter($stub->getValues(), is_scalar(...));
  }

  /**
   * Restores scalar values previously captured.
   *
   * @param \Drupal\Driver\Entity\EntityStubInterface $stub
   *   The entity stub to mutate.
   * @param array<string, scalar> $scalars
   *   Map of value name to original scalar value.
   */
  protected function restoreScalarBaseFields(EntityStubInterface $stub, array $scalars): void {
    foreach ($scalars as $field => $value) {
      $stub->setValue($field, $value);
    }
  }

  /**
   * Returns an existing term created during the test.
   *
   * @param string $name
   *   The term name to search for.
   * @param string $vocabulary
   *   The vocabulary machine name.
   *
   * @return \Drupal\Driver\Entity\EntityStubInterface|null
   *   The term stub or NULL if no matching term was found.
   */
  protected function getExistingTerm(string $name, string $vocabulary): ?EntityStubInterface {
    foreach ($this->createdStubs as $stub) {
      if ($stub->getEntityType() !== 'taxonomy_term') {
        continue;
      }

      if ($stub->getValue('name') === $name && $stub->getValue('vocabulary_machine_name') === $vocabulary) {
        return $stub;
      }
    }

    return NULL;
  }

  /**
   * Creates a language.
   *
   * @param \Drupal\Driver\Entity\EntityStubInterface $stub
   *   Language stub. Must carry a 'langcode' value.
   *
   * @return \Drupal\Driver\Entity\EntityStubInterface|false
   *   The created language stub, or FALSE if the language was already
   *   created.
   */
  public function languageCreate(EntityStubInterface $stub): EntityStubInterface|false {
    $this->dispatchHooks(BeforeLanguageCreateScope::class, $stub);

    $driver = $this->getDriver();

    if (!$driver instanceof LanguageCapabilityInterface) {
      throw new \RuntimeException(sprintf('The active Drupal driver "%s" does not support language management.', $driver::class));
    }

    $result = $driver->languageCreate($stub);

    if ($result === FALSE) {
      return FALSE;
    }

    $this->dispatchHooks(AfterLanguageCreateScope::class, $result);
    $this->createdStubs[] = $result;

    return $result;
  }

  /**
   * Log-in the given user.
   *
   * @param \Drupal\Driver\Entity\EntityStubInterface $user
   *   The user stub to log in.
   */
  public function login(EntityStubInterface $user): void {
    $this->getAuthenticationManager()->logIn($user);
  }

  /**
   * Logs the current user out.
   *
   * @param bool $fast
   *   Utilize direct logout by session if available.
   */
  public function logout($fast = FALSE): void {
    if ($fast && $this->getAuthenticationManager() instanceof FastLogoutInterface) {
      $this->getAuthenticationManager()->fastLogout();
    }
    else {
      $this->getAuthenticationManager()->logOut();
    }
  }

  /**
   * Determine if the a user is already logged in.
   *
   * @return bool
   *   Returns TRUE if a user is logged in for this session.
   */
  public function loggedIn() {
    return $this->getAuthenticationManager()->loggedIn();
  }

  /**
   * Resolves the active driver as a content-capable instance.
   *
   * @throws \RuntimeException
   *   When the active driver does not implement
   *   'ContentCapabilityInterface'.
   */
  protected function getContentDriver(): ContentCapabilityInterface {
    $driver = $this->getDriver();

    if (!$driver instanceof ContentCapabilityInterface) {
      throw new \RuntimeException(sprintf('The active Drupal driver "%s" does not support content creation.', $driver::class));
    }

    return $driver;
  }

  /**
   * Returns the Behat context that corresponds with the given class name.
   *
   * This is inspired by InitializedContextEnvironment::getContext() but also
   * returns subclasses of the given class name. This allows us to retrieve for
   * example DrupalContext even if it is overridden in a project.
   *
   * @param string $class
   *   A fully namespaced class name.
   *
   * @return \Behat\Behat\Context\Context|false
   *   The requested context, or FALSE if the context is not registered.
   *
   * @throws \RuntimeException
   *   Thrown when the environment is not yet initialized, meaning that contexts
   *   cannot yet be retrieved.
   */
  protected function getContext($class): object|false {
    $environment = $this->drupalDriverManager->getEnvironment();
    // Throw an exception if the environment is not yet initialized. To make
    // sure state doesn't leak between test scenarios, the environment is
    // reinitialized at the start of every scenario. If this code is executed
    // before a test scenario starts (e.g. in a `@BeforeScenario` hook) then the
    // contexts cannot yet be retrieved.
    if (!$environment instanceof InitializedContextEnvironment) {
      throw new \RuntimeException('Cannot retrieve contexts when the environment is not yet initialized.');
    }
    foreach ($environment->getContexts() as $context) {
      if ($context instanceof $class) {
        return $context;
      }
    }

    return FALSE;
  }

  /**
   * Returns the paths to the translation resources for the Drupal extension.
   *
   * @return array<int, string>
   *   List of translation resource paths.
   */
  protected static function getDrupalTranslationResources(): array {
    return glob(__DIR__ . '/../../../../i18n/*.xliff') ?: [];
  }

}
