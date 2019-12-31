<?php

namespace Drupal\DrupalExtension\Context;

use Behat\MinkExtension\Context\RawMinkContext;
use Behat\Mink\Exception\DriverException;
use Behat\Testwork\Hook\HookDispatcher;
use Behat\Behat\Context\Environment\InitializedContextEnvironment;

use Drupal\DrupalDriverManager;
use Drupal\DrupalExtension\DrupalParametersTrait;
use Drupal\DrupalExtension\Manager\DrupalAuthenticationManagerInterface;
use Drupal\DrupalExtension\Manager\DrupalUserManagerInterface;

use Drupal\DrupalExtension\Hook\Scope\AfterLanguageEnableScope;
use Drupal\DrupalExtension\Hook\Scope\AfterNodeCreateScope;
use Drupal\DrupalExtension\Hook\Scope\AfterTermCreateScope;
use Drupal\DrupalExtension\Hook\Scope\AfterUserCreateScope;
use Drupal\DrupalExtension\Hook\Scope\BaseEntityScope;
use Drupal\DrupalExtension\Hook\Scope\BeforeLanguageEnableScope;
use Drupal\DrupalExtension\Hook\Scope\BeforeNodeCreateScope;
use Drupal\DrupalExtension\Hook\Scope\BeforeUserCreateScope;
use Drupal\DrupalExtension\Hook\Scope\BeforeTermCreateScope;
use Drupal\DrupalExtension\Manager\FastLogoutInterface;

/**
 * Provides the raw functionality for interacting with Drupal.
 */
class RawDrupalContext extends RawMinkContext implements DrupalAwareInterface
{

    use DrupalParametersTrait;

  /**
   * Drupal driver manager.
   *
   * @var \Drupal\DrupalDriverManager
   */
    private $drupal;

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
    protected $nodes = array();

  /**
   * Keep track of all terms that are created so they can easily be removed.
   *
   * @var array
   */
    protected $terms = array();

  /**
   * Keep track of any roles that are created so they can easily be removed.
   *
   * @var array
   */
    protected $roles = array();

  /**
   * Keep track of any languages that are created so they can easily be removed.
   *
   * @var array
   */
    protected $languages = array();

  /**
   * {@inheritDoc}
   */
    public function setDrupal(DrupalDriverManager $drupal)
    {
        $this->drupal = $drupal;
    }

  /**
   * {@inheritDoc}
   */
    public function getDrupal()
    {
        return $this->drupal;
    }

  /**
   * {@inheritDoc}
   */
    public function setUserManager(DrupalUserManagerInterface $userManager)
    {
        $this->userManager = $userManager;
    }

  /**
   * {@inheritdoc}
   */
    public function getUserManager()
    {
        return $this->userManager;
    }

  /**
   * {@inheritdoc}
   */
    public function setAuthenticationManager(DrupalAuthenticationManagerInterface $authenticationManager)
    {
        $this->authenticationManager = $authenticationManager;
    }

  /**
   * {@inheritdoc}
   */
    public function getAuthenticationManager()
    {
        return $this->authenticationManager;
    }

  /**
   * Magic setter.
   */
    public function __set($name, $value)
    {
        switch ($name) {
            case 'user':
                trigger_error('Interacting directly with the RawDrupalContext::$user property has been deprecated. Use RawDrupalContext::getUserManager->setCurrentUser() instead.', E_USER_DEPRECATED);
                // Set the user on the user manager service, so it is shared between all
                // contexts.
                $this->getUserManager()->setCurrentUser($value);
                break;

            case 'users':
                trigger_error('Interacting directly with the RawDrupalContext::$users property has been deprecated. Use RawDrupalContext::getUserManager->addUser() instead.', E_USER_DEPRECATED);
                // Set the user on the user manager service, so it is shared between all
                // contexts.
                if (empty($value)) {
                    $this->getUserManager()->clearUsers();
                } else {
                    foreach ($value as $user) {
                        $this->getUserManager()->addUser($user);
                    }
                }
                break;
        }
    }

  /**
   * Magic getter.
   */
    public function __get($name)
    {
        switch ($name) {
            case 'user':
                trigger_error('Interacting directly with the RawDrupalContext::$user property has been deprecated. Use RawDrupalContext::getUserManager->getCurrentUser() instead.', E_USER_DEPRECATED);
                // Returns the current user from the user manager service. This is shared
                // between all contexts.
                return $this->getUserManager()->getCurrentUser();

            case 'users':
                trigger_error('Interacting directly with the RawDrupalContext::$users property has been deprecated. Use RawDrupalContext::getUserManager->getUsers() instead.', E_USER_DEPRECATED);
                // Returns the current user from the user manager service. This is shared
                // between all contexts.
                return $this->getUserManager()->getUsers();
        }
    }

  /**
   * {@inheritdoc}
   */
    public function setDispatcher(HookDispatcher $dispatcher)
    {
        $this->dispatcher = $dispatcher;
    }

  /**
   * Get active Drupal Driver.
   *
   * @return \Drupal\Driver\DrupalDriver
   */
    public function getDriver($name = null)
    {
        return $this->getDrupal()->getDriver($name);
    }

  /**
   * Get driver's random generator.
   */
    public function getRandom()
    {
        return $this->getDriver()->getRandom();
    }

  /**
   * Massage node values to match the expectations on different Drupal versions.
   *
   * @beforeNodeCreate
   */
    public static function alterNodeParameters(BeforeNodeCreateScope $scope)
    {
        $node = $scope->getEntity();

        // Get the Drupal API version if available. This is not available when
        // using e.g. the BlackBoxDriver or DrushDriver.
        $api_version = null;
        $driver = $scope->getContext()->getDrupal()->getDriver();
        if ($driver instanceof \Drupal\Driver\DrupalDriver) {
            $api_version = $scope->getContext()->getDrupal()->getDriver()->version;
        }

        // On Drupal 8 the timestamps should be in UNIX time.
        switch ($api_version) {
            case 8:
                foreach (array('changed', 'created', 'revision_timestamp') as $field) {
                    if (!empty($node->$field) && !is_numeric($node->$field)) {
                        $node->$field = strtotime($node->$field);
                    }
                }
                break;
        }
    }

  /**
   * Remove any created nodes.
   *
   * @AfterScenario
   */
    public function cleanNodes()
    {
        // Remove any nodes that were created.
        foreach ($this->nodes as $node) {
            $this->getDriver()->nodeDelete($node);
        }
        $this->nodes = array();
    }

  /**
   * Remove any created users.
   *
   * @AfterScenario
   */
    public function cleanUsers()
    {
        // Remove any users that were created.
        if ($this->userManager->hasUsers()) {
            foreach ($this->userManager->getUsers() as $user) {
                $this->getDriver()->userDelete($user);
            }
            $this->getDriver()->processBatch();
            $this->userManager->clearUsers();
            // If the authentication manager supports logout, no need to check if the user is logged in.
            if ($this->getAuthenticationManager() instanceof FastLogoutInterface) {
                $this->logout(true);
            } elseif ($this->loggedIn()) {
                $this->logout();
            }
        }
    }

  /**
   * Remove any created terms.
   *
   * @AfterScenario
   */
    public function cleanTerms()
    {
        // Remove any terms that were created.
        foreach ($this->terms as $term) {
            $this->getDriver()->termDelete($term);
        }
        $this->terms = array();
    }

  /**
   * Remove any created roles.
   *
   * @AfterScenario
   */
    public function cleanRoles()
    {
        // Remove any roles that were created.
        foreach ($this->roles as $rid) {
            $this->getDriver()->roleDelete($rid);
        }
        $this->roles = array();
    }

  /**
   * Remove any created languages.
   *
   * @AfterScenario
   */
    public function cleanLanguages()
    {
        // Delete any languages that were created.
        foreach ($this->languages as $language) {
            $this->getDriver()->languageDelete($language);
            unset($this->languages[$language->langcode]);
        }
    }

  /**
   * Clear static caches.
   *
   * @AfterScenario @api
   */
    public function clearStaticCaches()
    {
        $this->getDriver()->clearStaticCaches();
    }

  /**
   * Dispatch scope hooks.
   *
   * @param string $scope
   *   The entity scope to dispatch.
   * @param \stdClass $entity
   *   The entity.
   */
    protected function dispatchHooks($scopeType, \stdClass $entity)
    {
        $fullScopeClass = 'Drupal\\DrupalExtension\\Hook\\Scope\\' . $scopeType;
        $scope = new $fullScopeClass($this->getDrupal()->getEnvironment(), $this, $entity);
        $callResults = $this->dispatcher->dispatchScopeHooks($scope);

        // The dispatcher suppresses exceptions, throw them here if there are any.
        foreach ($callResults as $result) {
            if ($result->hasException()) {
                $exception = $result->getException();
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
    public function nodeCreate($node)
    {
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
    public function parseEntityFields($entity_type, \stdClass $entity)
    {
        $multicolumn_field = '';
        $multicolumn_fields = array();

        foreach (clone $entity as $field => $field_value) {
            // Reset the multicolumn field if the field name does not contain a column.
            if (strpos($field, ':') === false) {
                $multicolumn_field = '';
            } elseif (strpos($field, ':', 1) !== false) {
                // Start tracking a new multicolumn field if the field name contains a ':'
                // which is preceded by at least 1 character.
                list($multicolumn_field, $multicolumn_column) = explode(':', $field);
            } elseif (empty($multicolumn_field)) {
                // If a field name starts with a ':' but we are not yet tracking a
                // multicolumn field we don't know to which field this belongs.
                throw new \Exception('Field name missing for ' . $field);
            } else {
                // Update the column name if the field name starts with a ':' and we are
                // already tracking a multicolumn field.
                $multicolumn_column = substr($field, 1);
            }

            $is_multicolumn = $multicolumn_field && $multicolumn_column;
            $field_name = $multicolumn_field ?: $field;
            if ($this->getDriver()->isField($entity_type, $field_name)) {
                // Split up multiple values in multi-value fields.
                $values = array();
                foreach (str_getcsv($field_value) as $key => $value) {
                    $value = trim($value);
                    $columns = $value;
                    // Split up field columns if the ' - ' separator is present.
                    if (strstr($value, ' - ') !== false) {
                        $columns = array();
                        foreach (explode(' - ', $value) as $column) {
                            // Check if it is an inline named column.
                            if (!$is_multicolumn && strpos($column, ': ', 1) !== false) {
                                list ($key, $column) = explode(': ', $column);
                                $columns[$key] = $column;
                            } else {
                                $columns[] = $column;
                            }
                        }
                    }
                    // Use the column name if we are tracking a multicolumn field.
                    if ($is_multicolumn) {
                        $multicolumn_fields[$multicolumn_field][$key][$multicolumn_column] = $columns;
                        unset($entity->$field);
                    } else {
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
        }

        // Add the multicolumn fields to the entity.
        foreach ($multicolumn_fields as $field_name => $columns) {
            // Don't specify any value if the step author has left it blank.
            if (count(array_filter($columns, function ($var) {
                return ($var !== '');
            })) > 0) {
                $entity->$field_name = $columns;
            }
        }
    }

  /**
   * Create a user.
   *
   * @return object
   *   The created user.
   */
    public function userCreate($user)
    {
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
    public function termCreate($term)
    {
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
   * @return object|FALSE
   *   The created language, or FALSE if the language was already created.
   */
    public function languageCreate(\stdClass $language)
    {
        $this->dispatchHooks('BeforeLanguageCreateScope', $language);
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
    public function login(\stdClass $user)
    {
        $this->getAuthenticationManager()->logIn($user);
    }

  /**
   * Logs the current user out.
   *
   * @param bool $fast
   *   Utilize direct logout by session if available.
   */
    public function logout($fast = false)
    {
        if ($fast && $this->getAuthenticationManager() instanceof FastLogoutInterface) {
            $this->getAuthenticationManager()->fastLogout();
        } else {
            $this->getAuthenticationManager()->logOut();
        }
    }

  /**
   * Determine if the a user is already logged in.
   *
   * @return boolean
   *   Returns TRUE if a user is logged in for this session.
   */
    public function loggedIn()
    {
        return $this->getAuthenticationManager()->loggedIn();
    }

  /**
   * User with a given role is already logged in.
   *
   * @param string $role
   *   A single role, or multiple comma-separated roles in a single string.
   *
   * @return boolean
   *   Returns TRUE if the current logged in user has this role (or roles).
   */
    public function loggedInWithRole($role)
    {
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
    protected function getContext($class)
    {
        /** @var InitializedContextEnvironment $environment */
        $environment = $this->drupal->getEnvironment();
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

        return false;
    }
}
