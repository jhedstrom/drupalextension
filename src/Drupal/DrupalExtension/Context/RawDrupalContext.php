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
use Drupal\DrupalExtension\Hook\Attribute\BeforeNodeCreate;
use Drupal\taxonomy\Entity\Vocabulary;
use Behat\MinkExtension\Context\RawMinkContext;
use Behat\Testwork\Hook\HookDispatcher;
use Behat\Behat\Context\Environment\InitializedContextEnvironment;

use Drupal\DrupalDriverManagerInterface;
use Drupal\DrupalExtension\DrupalParametersTrait;
use Drupal\DrupalExtension\Manager\DrupalAuthenticationManagerInterface;
use Drupal\DrupalExtension\Manager\DrupalUserManagerInterface;

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
   * Keep track of nodes so they can be cleaned up.
   *
   * @var array
   */
  protected $nodes = [];

  /**
   * Keep track of all terms that are created so they can easily be removed.
   *
   * @var array
   */
  protected $terms = [];

  /**
   * Keep track of any roles that are created so they can easily be removed.
   *
   * @var array
   */
  protected $roles = [];

  /**
   * Keep track of any languages that are created so they can easily be removed.
   *
   * @var array
   */
  protected $languages = [];

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
    $node = $scope->getEntity();

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
      if (!empty($node->$field) && !is_numeric($node->$field)) {
        $node->$field = strtotime((string) $node->$field);
      }
    }
  }

  /**
   * Remove any created nodes.
   */
  #[AfterScenario]
  public function cleanNodes(): void {
    if ($this->nodes === []) {
      return;
    }

    $driver = $this->getDriver();

    if (!$driver instanceof ContentCapabilityInterface) {
      return;
    }

    foreach ($this->nodes as $node) {
      $driver->nodeDelete($node);
    }

    $this->nodes = [];
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
   * Remove any created terms.
   */
  #[AfterScenario]
  public function cleanTerms(): void {
    if ($this->terms === []) {
      return;
    }

    $driver = $this->getDriver();

    if (!$driver instanceof ContentCapabilityInterface) {
      return;
    }

    foreach (array_reverse($this->terms) as $term) {
      $driver->termDelete($term);
    }

    $this->terms = [];
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
   * Remove any created languages.
   */
  #[AfterScenario]
  public function cleanLanguages(): void {
    if ($this->languages === []) {
      return;
    }

    $driver = $this->getDriver();

    if (!$driver instanceof LanguageCapabilityInterface) {
      return;
    }

    foreach ($this->languages as $language) {
      $driver->languageDelete($language);
      unset($this->languages[$language->langcode]);
    }
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
   * @param class-string $scopeClass
   *   The fully-qualified scope class name.
   * @param \stdClass $entity
   *   The entity.
   */
  protected function dispatchHooks(string $scopeClass, \stdClass $entity) {
    $scope = new $scopeClass($this->getDrupal()->getEnvironment(), $this, $entity);
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
   * @return object
   *   The created node.
   */
  public function nodeCreate(\stdClass $node): object {
    $this->dispatchHooks(BeforeNodeCreateScope::class, $node);
    $this->parseEntityFields('node', $node, ['author']);

    $driver = $this->getDriver();

    if (!$driver instanceof ContentCapabilityInterface) {
      throw new \RuntimeException(sprintf('The active Drupal driver "%s" does not support content creation.', $driver::class));
    }

    $scalars = $this->captureScalarBaseFields($node);
    $saved = $driver->nodeCreate($node);
    $this->restoreScalarBaseFields($saved, $scalars);

    $this->dispatchHooks(AfterNodeCreateScope::class, $saved);
    $this->nodes[] = $saved;

    return $saved;
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
   * Blank values remove the field from the entity so Drupal applies its
   * default.
   *
   * @param string $entity_type
   *   The entity type (e.g. 'node', 'taxonomy_term', 'user').
   * @param \stdClass $entity
   *   The entity object. Recognised field properties are replaced in-place
   *   with structured arrays; other properties are left unchanged.
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
  public function parseEntityFields(string $entity_type, \stdClass $entity, array $ignored_properties = []): void {
    $driver = $this->getDriver();

    if (!$driver instanceof DrupalDriver) {
      throw new \RuntimeException(sprintf('The active Drupal driver "%s" does not support field inspection.', $driver::class));
    }

    $classifier = $driver->getCore()->classifier();

    $multicolumn_field = '';
    $multicolumn_column = '';
    $multicolumn_fields = [];

    foreach ((array) (clone $entity) as $field => $field_value) {
      // Reset the multicolumn field if the field name does not have a column.
      if (!str_contains((string) $field, ':')) {
        $multicolumn_field = '';
        $multicolumn_column = '';
      }
      elseif (str_contains(substr((string) $field, 1), ':')) {
        // Start tracking a new multicolumn field if the field name contains a
        // ':' which is preceded by at least 1 character.
        [$multicolumn_field, $multicolumn_column] = explode(':', (string) $field);
      }
      elseif (empty($multicolumn_field)) {
        // If a field name starts with a ':' but we are not yet tracking a
        // multicolumn field we don't know to which field this belongs.
        throw new \RuntimeException('Field name missing for ' . $field);
      }
      else {
        // Update the column name if the field name starts with a ':' and we are
        // already tracking a multicolumn field.
        $multicolumn_column = substr((string) $field, 1);
      }

      $is_multicolumn = $multicolumn_field && $multicolumn_column;
      $field_name = $multicolumn_field ?: $field;
      if ($classifier->fieldIsConfigurable($entity_type, $field_name)) {
        // Split up multiple values in multi-value fields.
        $values = [];
        foreach (str_getcsv((string) $field_value, escape: "\\") as $key => $value) {
          $value = trim((string) $value);
          $columns = $value;
          // Split up field columns if the ' - ' separator is present.
          // Skip splitting if the value was double-quoted in the original
          // field value, allowing values like "Alpha - Bravo" to pass
          // through as-is (e.g., entity reference titles with dashes).
          // @see https://github.com/jhedstrom/drupalextension/issues/642
          $was_quoted = str_contains((string) $field_value, '"' . $value . '"');
          if (!$was_quoted && str_contains($value, ' - ')) {
            $columns = [];
            foreach (explode(' - ', $value) as $column) {
              // Check if it is an inline named column.
              if (!$is_multicolumn && str_contains(substr($column, 1), ': ')) {
                [$key, $column] = explode(': ', $column);
                $columns[$key] = $column;
              }
              else {
                $columns[] = $column;
              }
            }
          }

          // Use the column name if we are tracking a multicolumn field.
          if ($is_multicolumn) {
            $multicolumn_fields[$multicolumn_field][$key][$multicolumn_column] = $columns;
            unset($entity->$field);
          }
          else {
            $values[] = $columns;
          }
        }

        // Replace regular fields inline in the entity after parsing.
        if (!$is_multicolumn) {
          $entity->$field_name = $values;
          // Don't specify any value if the step author has left it blank.
          if ($field_value === '') {
            unset($entity->$field_name);
          }
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
      }
    }

    // Add the multicolumn fields to the entity.
    foreach ($multicolumn_fields as $field_name => $columns) {
      // Don't specify any value if the step author has left it blank.
      if (count(array_filter($columns, fn($var): bool => $var !== '')) > 0) {
        $entity->$field_name = $columns;
      }
    }
  }

  /**
   * Create a user.
   *
   * @return \stdClass
   *   The created user.
   */
  public function userCreate(\stdClass $user): \stdClass {
    $this->dispatchHooks(BeforeUserCreateScope::class, $user);
    $this->parseEntityFields('user', $user, ['role']);

    $driver = $this->getDriver();

    if (!$driver instanceof UserCapabilityInterface) {
      throw new \RuntimeException(sprintf('The active Drupal driver "%s" does not support user creation.', $driver::class));
    }

    $scalars = $this->captureScalarBaseFields($user);
    $driver->userCreate($user);
    $this->restoreScalarBaseFields($user, $scalars);

    $this->dispatchHooks(AfterUserCreateScope::class, $user);
    $this->userManager->addUser($user);

    return $user;
  }

  /**
   * Create a term.
   *
   * @return object
   *   The created term.
   */
  public function termCreate(\stdClass $term): object {
    // The 3.x DrupalDriver only loads vocabularies by machine name. Allow
    // the Gherkin author to pass either the machine name or the human
    // label by resolving the label to a machine name when the literal
    // string does not match a vocabulary id. Resolution is best-effort -
    // the driver throws a clearer error than we could when the lookup
    // ultimately fails.
    if (!empty($term->vocabulary_machine_name)) {
      $term->vocabulary_machine_name = $this->resolveVocabularyMachineName($term->vocabulary_machine_name);
    }

    // The 3.x DrupalDriver resolves a 'parent' property as a term name
    // against the configured vocabulary and throws if it does not exist;
    // pass the name through unchanged. An empty 'parent' must be removed
    // so the field-handler pipeline does not try to expand the empty
    // string as an entity reference.
    if (empty($term->parent)) {
      unset($term->parent);
    }

    $this->dispatchHooks(BeforeTermCreateScope::class, $term);
    $this->parseEntityFields('taxonomy_term', $term, ['vocabulary_machine_name']);

    $driver = $this->getDriver();

    if (!$driver instanceof ContentCapabilityInterface) {
      throw new \RuntimeException(sprintf('The active Drupal driver "%s" does not support term creation.', $driver::class));
    }

    $scalars = $this->captureScalarBaseFields($term);
    $saved = $driver->termCreate($term);
    $this->restoreScalarBaseFields($saved, $scalars);

    $this->dispatchHooks(AfterTermCreateScope::class, $saved);
    $this->terms[] = $saved;

    return $saved;
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
   * Captures scalar properties on an entity stub.
   *
   * The 3.x DrupalDriver runs base fields through the field-handler
   * pipeline during create, which casts scalar properties such as
   * 'title', 'name', 'mail' or 'pass' to single-element arrays. Most
   * drupalextension downstream code (user manager indexing, login flow,
   * stub matching) expects scalars, so callers snapshot the scalars
   * before the driver call and restore them after.
   *
   * @param object $entity
   *   The entity stub to inspect.
   *
   * @return array<string, scalar>
   *   The scalar properties keyed by name.
   */
  protected function captureScalarBaseFields(object $entity): array {
    return array_filter((array) $entity, is_scalar(...));
  }

  /**
   * Restores scalar properties previously captured.
   *
   * @param object $entity
   *   The entity stub to mutate.
   * @param array<string, scalar> $scalars
   *   Map of property name to original scalar value.
   */
  protected function restoreScalarBaseFields(object $entity, array $scalars): void {
    foreach ($scalars as $field => $value) {
      $entity->$field = $value;
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
   * @return \stdClass|null
   *   The term object or NULL if no matching term was found.
   */
  protected function getExistingTerm(string $name, string $vocabulary): ?\stdClass {
    foreach ($this->terms as $term) {
      if ($term->name === $name && $term->vocabulary_machine_name === $vocabulary) {
        return $term;
      }
    }

    return NULL;
  }

  /**
   * Creates a language.
   *
   * @param \stdClass $language
   *   An object with the following properties:
   *   - langcode: the langcode of the language to create.
   *
   * @return \stdClass|false
   *   The created language, or FALSE if the language was already created.
   */
  public function languageCreate(\stdClass $language): \stdClass|false {
    $this->dispatchHooks(BeforeLanguageCreateScope::class, $language);

    $driver = $this->getDriver();

    if (!$driver instanceof LanguageCapabilityInterface) {
      throw new \RuntimeException(sprintf('The active Drupal driver "%s" does not support language management.', $driver::class));
    }

    $language = $driver->languageCreate($language);

    if ($language) {
      $this->dispatchHooks(AfterLanguageCreateScope::class, $language);
      $this->languages[$language->langcode] = $language;
    }

    return $language;
  }

  /**
   * Log-in the given user.
   *
   * @param \stdClass $user
   *   The user to log in.
   */
  public function login(\stdClass $user): void {
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
   * User with a given role is already logged in.
   *
   * @param string $role
   *   A single role, or multiple comma-separated roles in a single string.
   *
   * @return bool
   *   Returns TRUE if the current logged in user has this role (or roles).
   *
   * @deprecated in drupalextension:5.3.0 and is removed from
   *   drupalextension:6.0.0. Role-based login steps no longer check the
   *   current user's role before creating a new user.
   */
  public function loggedInWithRole($role): bool {
    @trigger_error("loggedInWithRole() is deprecated in drupalextension:5.3.0 and is removed from drupalextension:6.0.0. Role-based login steps no longer check the current user's role before creating a new user. See https://www.drupal.org/project/drupalextension/issues/676", E_USER_DEPRECATED);
    return $this->loggedIn() && $this->getUserManager()->currentUserHasRole($role);
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
   * @throws \Exception
   *   Thrown when the environment is not yet initialized, meaning that contexts
   *   cannot yet be retrieved.
   */
  protected function getContext($class): object|false {
    /** @var \Behat\Behat\Context\Environment\InitializedContextEnvironment $environment */
    $environment = $this->drupalDriverManager->getEnvironment();
    // Throw an exception if the environment is not yet initialized. To make
    // sure state doesn't leak between test scenarios, the environment is
    // reinitialized at the start of every scenario. If this code is executed
    // before a test scenario starts (e.g. in a `@BeforeScenario` hook) then the
    // contexts cannot yet be retrieved.
    if (!$environment instanceof InitializedContextEnvironment) {
      throw new \Exception('Cannot retrieve contexts when the environment is not yet initialized.');
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
   * @return array
   *   List of translation resource paths.
   */
  protected static function getDrupalTranslationResources(): array {
    return glob(__DIR__ . '/../../../../i18n/*.xliff');
  }

}
