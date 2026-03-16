<?php

declare(strict_types=1);

namespace Drupal\DrupalExtension\Context;

use Drupal\Driver\DrupalDriver;
use Behat\MinkExtension\Context\RawMinkContext;
use Behat\Testwork\Hook\HookDispatcher;
use Behat\Behat\Context\Environment\InitializedContextEnvironment;

use Drupal\DrupalDriverManagerInterface;
use Drupal\DrupalExtension\DrupalParametersTrait;
use Drupal\DrupalExtension\Manager\DrupalAuthenticationManagerInterface;
use Drupal\DrupalExtension\Manager\DrupalUserManagerInterface;

use Drupal\DrupalExtension\Hook\Scope\BeforeNodeCreateScope;
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
  public function getRandom() {
    return $this->getDriver()->getRandom();
  }

  /**
   * Massage node values to match the expectations on different Drupal versions.
   *
   * @beforeNodeCreate
   */
  public static function alterNodeParameters(BeforeNodeCreateScope $scope): void {
    $node = $scope->getEntity();

    // Get the Drupal API version if available. This is not available when
    // using e.g. the BlackBoxDriver or DrushDriver.
    $apiVersion = NULL;
    $context = $scope->getContext();
    if ($context instanceof DrupalAwareInterface) {
      $driver = $context->getDrupal()->getDriver();
      if ($driver instanceof DrupalDriver) {
        // @phpstan-ignore-next-line property.notFound
        $apiVersion = $context->getDrupal()->getDriver()->version;
      }
    }

    if ($apiVersion === 8) {
      foreach (['changed', 'created', 'revision_timestamp'] as $field) {
        if (!empty($node->$field) && !is_numeric($node->$field)) {
          $node->$field = strtotime((string) $node->$field);
        }
      }
    }
  }

  /**
   * Remove any created nodes.
   *
   * @AfterScenario
   */
  public function cleanNodes(): void {
    // Remove any nodes that were created.
    foreach ($this->nodes as $node) {
      $this->getDriver()->nodeDelete($node);
    }
    $this->nodes = [];
  }

  /**
   * Remove any created users.
   *
   * @AfterScenario
   */
  public function cleanUsers(): void {
    // Remove any users that were created.
    if ($this->userManager->hasUsers()) {
      foreach ($this->userManager->getUsers() as $user) {
        $this->getDriver()->userDelete($user);
      }
      $this->getDriver()->processBatch();
      $this->userManager->clearUsers();
      // If the authentication manager supports logout, no need to check if the user is logged in.
      if ($this->getAuthenticationManager() instanceof FastLogoutInterface) {
        $this->logout(TRUE);
      }
      elseif ($this->loggedIn()) {
        $this->logout();
      }
    }
  }

  /**
   * Remove any created terms.
   *
   * @AfterScenario
   */
  public function cleanTerms(): void {
    // Remove any terms that were created.
    foreach (array_reverse($this->terms) as $term) {
      $this->getDriver()->termDelete($term);
    }
    $this->terms = [];
  }

  /**
   * Remove any created roles.
   *
   * @AfterScenario
   */
  public function cleanRoles(): void {
    // Remove any roles that were created.
    foreach ($this->roles as $role) {
      $this->getDriver()->roleDelete($role);
    }
    $this->roles = [];
  }

  /**
   * Remove any created languages.
   *
   * @AfterScenario
   */
  public function cleanLanguages(): void {
    // Delete any languages that were created.
    foreach ($this->languages as $language) {
      // @phpstan-ignore method.notFound
      $this->getDriver()->languageDelete($language);
      unset($this->languages[$language->langcode]);
    }
  }

  /**
   * Clear static caches.
   *
   * @AfterScenario @api
   */
  public function clearStaticCaches(): void {
    $this->getDriver()->clearStaticCaches();
  }

  /**
   * Dispatch scope hooks.
   *
   * @param string $scopeType
   *   The entity scope to dispatch.
   * @param \stdClass $entity
   *   The entity.
   */
  protected function dispatchHooks(string $scopeType, \stdClass $entity) {
    $fullScopeClass = 'Drupal\\DrupalExtension\\Hook\\Scope\\' . $scopeType;
    $scope = new $fullScopeClass($this->getDrupal()->getEnvironment(), $this, $entity);
    $callResults = $this->dispatcher->dispatchScopeHooks($scope);

    // The dispatcher suppresses exceptions, throw them here if there are any.
    foreach ($callResults as $callResult) {
      if ($callResult->hasException()) {
        $exception = $callResult->getException();
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
  public function nodeCreate(\stdClass $node) {
    $this->dispatchHooks('BeforeNodeCreateScope', $node);
    $this->parseEntityFields('node', $node);
    $saved = $this->getDriver()->createNode($node);
    $this->dispatchHooks('AfterNodeCreateScope', $saved);
    $this->nodes[] = $saved;
    return $saved;
  }

  /**
   * Parses the field values and turns them into the format expected by Drupal.
   *
   * Multiple values in a single field must be separated by commas. Wrap the
   * field value in double quotes in case it should contain a comma.
   *
   * Compound field properties are identified using a ':' operator, either in
   * the column heading or in the cell. If multiple properties are present in a
   * single cell, they must be separated using ' - ', and values should not
   * contain ':' or ' - '.
   *
   * Possible formats for the values:
   *   A
   *   A, B, "a value, containing a comma"
   *   A - B
   *   x: A - y: B
   *   A - B, C - D, "E - F"
   *   x: A - y: B,  x: C - y: D,  "x: E - y: F"
   *
   * See field_handlers.feature for examples of usage.
   *
   * @param string $entity_type
   *   The entity type.
   * @param \stdClass $entity
   *   An object containing the entity properties and fields as properties.
   *
   * @throws \Exception
   *   Thrown when a field name is invalid.
   */
  public function parseEntityFields(string $entity_type, \stdClass $entity): void {
    $multicolumnField = '';
    $multicolumnColumn = '';
    $multicolumnFields = [];

    foreach ((array) (clone $entity) as $field => $fieldValue) {
      // Reset the multicolumn field if the field name does not contain a column.
      if (!str_contains((string) $field, ':')) {
        $multicolumnField = '';
        $multicolumnColumn = '';
      }
      elseif (str_contains(substr((string) $field, 1), ':')) {
        // Start tracking a new multicolumn field if the field name contains a ':'
        // which is preceded by at least 1 character.
        [$multicolumnField, $multicolumnColumn] = explode(':', (string) $field);
      }
      elseif (empty($multicolumnField)) {
        // If a field name starts with a ':' but we are not yet tracking a
        // multicolumn field we don't know to which field this belongs.
        throw new \Exception('Field name missing for ' . $field);
      }
      else {
        // Update the column name if the field name starts with a ':' and we are
        // already tracking a multicolumn field.
        $multicolumnColumn = substr((string) $field, 1);
      }

      $isMulticolumn = $multicolumnField && $multicolumnColumn;
      $fieldName = $multicolumnField ?: $field;
      if ($this->getDriver()->isField($entity_type, $fieldName)) {
        // Split up multiple values in multi-value fields.
        $values = [];
        foreach (str_getcsv((string) $fieldValue, escape: "\\") as $key => $value) {
          $value = trim((string) $value);
          $columns = $value;
          // Split up field columns if the ' - ' separator is present.
          if (str_contains($value, ' - ')) {
            $columns = [];
            foreach (explode(' - ', $value) as $column) {
              // Check if it is an inline named column.
              if (!$isMulticolumn && str_contains(substr($column, 1), ': ')) {
                [$key, $column] = explode(': ', $column);
                $columns[$key] = $column;
              }
              else {
                $columns[] = $column;
              }
            }
          }
          // Use the column name if we are tracking a multicolumn field.
          if ($isMulticolumn) {
            $multicolumnFields[$multicolumnField][$key][$multicolumnColumn] = $columns;
            unset($entity->$field);
          }
          else {
            $values[] = $columns;
          }
        }
        // Replace regular fields inline in the entity after parsing.
        if (!$isMulticolumn) {
          $entity->$fieldName = $values;
          // Don't specify any value if the step author has left it blank.
          if ($fieldValue === '') {
            unset($entity->$fieldName);
          }
        }
      }
    }

    // Add the multicolumn fields to the entity.
    foreach ($multicolumnFields as $fieldName => $columns) {
      // Don't specify any value if the step author has left it blank.
      if (count(array_filter($columns, fn($var): bool => $var !== '')) > 0) {
        $entity->$fieldName = $columns;
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
    $this->dispatchHooks('BeforeUserCreateScope', $user);
    $this->parseEntityFields('user', $user);
    $this->getDriver()->userCreate($user);
    $this->dispatchHooks('AfterUserCreateScope', $user);
    $this->userManager->addUser($user);
    return $user;
  }

  /**
   * Create a term.
   *
   * @return object
   *   The created term.
   */
  public function termCreate(\stdClass $term) {
    $this->dispatchHooks('BeforeTermCreateScope', $term);
    $this->parseEntityFields('taxonomy_term', $term);
    $saved = $this->getDriver()->createTerm($term);
    $this->dispatchHooks('AfterTermCreateScope', $saved);
    $this->terms[] = $saved;
    return $saved;
  }

  /**
   * Creates a language.
   *
   * @param \stdClass $language
   *   An object with the following properties:
   *   - langcode: the langcode of the language to create.
   *
   * @return object|false
   *   The created language, or FALSE if the language was already created.
   */
  public function languageCreate(\stdClass $language) {
    $this->dispatchHooks('BeforeLanguageCreateScope', $language);
    // @phpstan-ignore method.notFound
    $language = $this->getDriver()->languageCreate($language);
    if ($language) {
      $this->dispatchHooks('AfterLanguageCreateScope', $language);
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
    @trigger_error("loggedInWithRole() is deprecated in drupalextension:5.3.0 and is removed from drupalextension:6.0.0. Role-based login steps no longer check the current user's role before creating a new user.", E_USER_DEPRECATED);
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

}
