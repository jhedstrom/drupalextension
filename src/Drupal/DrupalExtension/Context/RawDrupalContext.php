<?php

namespace Drupal\DrupalExtension\Context;

use Behat\MinkExtension\Context\RawMinkContext;
use Behat\Mink\Exception\DriverException;
use Behat\Testwork\Hook\HookDispatcher;

use Drupal\DrupalDriverManager;
use Drupal\DrupalUserManagerInterface;

use Drupal\DrupalExtension\Hook\Scope\AfterLanguageEnableScope;
use Drupal\DrupalExtension\Hook\Scope\AfterNodeCreateScope;
use Drupal\DrupalExtension\Hook\Scope\AfterTermCreateScope;
use Drupal\DrupalExtension\Hook\Scope\AfterUserCreateScope;
use Drupal\DrupalExtension\Hook\Scope\BaseEntityScope;
use Drupal\DrupalExtension\Hook\Scope\BeforeLanguageEnableScope;
use Drupal\DrupalExtension\Hook\Scope\BeforeNodeCreateScope;
use Drupal\DrupalExtension\Hook\Scope\BeforeUserCreateScope;
use Drupal\DrupalExtension\Hook\Scope\BeforeTermCreateScope;


/**
 * Provides the raw functionality for interacting with Drupal.
 */
class RawDrupalContext extends RawMinkContext implements DrupalAwareInterface {

  /**
   * Drupal driver manager.
   *
   * @var \Drupal\DrupalDriverManager
   */
  private $drupal;

  /**
   * Test parameters.
   *
   * @var array
   */
  private $drupalParameters;

  /**
   * Event dispatcher object.
   *
   * @var \Behat\Testwork\Hook\HookDispatcher
   */
  protected $dispatcher;

  /**
   * Drupal user manager.
   *
   * @var \Drupal\DrupalUserManagerInterface
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
  public function setDrupal(DrupalDriverManager $drupal) {
    $this->drupal = $drupal;
  }

  /**
   * {@inheritDoc}
   */
  public function getDrupal() {
    return $this->drupal;
  }

  /**
   * {@inheritDoc}
   */
  public function setUserManager(DrupalUserManagerInterface $userManager) {
    $this->userManager = $userManager;
  }

  /**
   * {@inheritdoc}
   */
  public function getUserManager() {
    return $this->userManager;
  }

  /**
   * Magic setter.
   */
  public function __set($name, $value) {
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
        }
        else {
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
  public function __get($name) {
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
  public function setDispatcher(HookDispatcher $dispatcher) {
    $this->dispatcher = $dispatcher;
  }

  /**
   * Set parameters provided for Drupal.
   */
  public function setDrupalParameters(array $parameters) {
    $this->drupalParameters = $parameters;
  }

  /**
   * Returns a specific Drupal parameter.
   *
   * @param string $name
   *   Parameter name.
   *
   * @return mixed
   */
  public function getDrupalParameter($name) {
    return isset($this->drupalParameters[$name]) ? $this->drupalParameters[$name] : NULL;
  }

  /**
   * Returns a specific Drupal text value.
   *
   * @param string $name
   *   Text value name, such as 'log_out', which corresponds to the default 'Log
   *   out' link text.
   * @throws \Exception
   * @return
   */
  public function getDrupalText($name) {
    $text = $this->getDrupalParameter('text');
    if (!isset($text[$name])) {
      throw new \Exception(sprintf('No such Drupal string: %s', $name));
    }
    return $text[$name];
  }

  /**
   * Returns a specific css selector.
   *
   * @param $name
   *   string CSS selector name
   */
  public function getDrupalSelector($name) {
    $text = $this->getDrupalParameter('selectors');
    if (!isset($text[$name])) {
      throw new \Exception(sprintf('No such selector configured: %s', $name));
    }
    return $text[$name];
  }

  /**
   * Get active Drupal Driver.
   *
   * @return \Drupal\Driver\DrupalDriver
   */
  public function getDriver($name = NULL) {
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
  public function alterNodeParameters(BeforeNodeCreateScope $scope) {
    $node = $scope->getEntity();

    // Get the Drupal API version if available. This is not available when
    // using e.g. the BlackBoxDriver or DrushDriver.
    $api_version = NULL;
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
  public function cleanNodes() {
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
  public function cleanUsers() {
    // Remove any users that were created.
    if ($this->userManager->hasUsers()) {
      foreach ($this->userManager->getUsers() as $user) {
        $this->getDriver()->userDelete($user);
      }
      $this->getDriver()->processBatch();
      $this->userManager->clearUsers();
      if ($this->loggedIn()) {
        $this->logout();
      }
    }
  }

  /**
   * Remove any created terms.
   *
   * @AfterScenario
   */
  public function cleanTerms() {
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
  public function cleanRoles() {
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
  public function cleanLanguages() {
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
  public function clearStaticCaches() {
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
  protected function dispatchHooks($scopeType, \stdClass $entity) {
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
  public function nodeCreate($node) {
    $this->dispatchHooks('BeforeNodeCreateScope', $node);
    $this->parseEntityFields('node', $node);
    $saved = $this->getDriver()->createNode($node);
    $this->dispatchHooks('AfterNodeCreateScope', $saved);
    $this->nodes[] = $saved;
    return $saved;
  }

  /**
   * Parse multi-value fields. Possible formats:
   *    A, B, C
   *    A - B, C - D, E - F
   *
   * @param string $entity_type
   *   The entity type.
   * @param \stdClass $entity
   *   An object containing the entity properties and fields as properties.
   */
  public function parseEntityFields($entity_type, \stdClass $entity) {
    $multicolumn_field = '';
    $multicolumn_fields = array();

    foreach (clone $entity as $field => $field_value) {
      // Reset the multicolumn field if the field name does not contain a column.
      if (strpos($field, ':') === FALSE) {
        $multicolumn_field = '';
      }
      // Start tracking a new multicolumn field if the field name contains a ':'
      // which is preceded by at least 1 character.
      elseif (strpos($field, ':', 1) !== FALSE) {
        list($multicolumn_field, $multicolumn_column) = explode(':', $field);
      }
      // If a field name starts with a ':' but we are not yet tracking a
      // multicolumn field we don't know to which field this belongs.
      elseif (empty($multicolumn_field)) {
        throw new \Exception('Field name missing for ' . $field);
      }
      // Update the column name if the field name starts with a ':' and we are
      // already tracking a multicolumn field.
      else {
        $multicolumn_column = substr($field, 1);
      }

      $is_multicolumn = $multicolumn_field && $multicolumn_column;
      $field_name = $multicolumn_field ?: $field;
      if ($this->getDriver()->isField($entity_type, $field_name)) {
        // Split up multiple values in multi-value fields.
        $values = array();
        foreach (explode(', ', $field_value) as $key => $value) {
          $columns = $value;
          // Split up field columns if the ' - ' separator is present.
          if (strstr($value, ' - ') !== FALSE) {
            $columns = array();
            foreach (explode(' - ', $value) as $column) {
              // Check if it is an inline named column.
              if (!$is_multicolumn && strpos($column, ': ', 1) !== FALSE) {
                list ($key, $column) = explode(': ', $column);
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
        }
      }
    }

    // Add the multicolumn fields to the entity.
    foreach ($multicolumn_fields as $field_name => $columns) {
      $entity->$field_name = $columns;
    }
  }

  /**
   * Create a user.
   *
   * @return object
   *   The created user.
   */
  public function userCreate($user) {
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
  public function termCreate($term) {
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
  public function languageCreate(\stdClass $language) {
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
  public function login(\stdClass $user) {
    $manager = $this->getUserManager();

    // Check if logged in.
    if ($this->loggedIn()) {
      $this->logout();
    }

    $this->getSession()->visit($this->locatePath('/user'));
    $element = $this->getSession()->getPage();
    $element->fillField($this->getDrupalText('username_field'), $user->name);
    $element->fillField($this->getDrupalText('password_field'), $user->pass);
    $submit = $element->findButton($this->getDrupalText('log_in'));
    if (empty($submit)) {
      throw new \Exception(sprintf("No submit button at %s", $this->getSession()->getCurrentUrl()));
    }

    // Log in.
    $submit->click();

    if (!$this->loggedIn()) {
      if (isset($user->role)) {
        throw new \Exception(sprintf("Unable to determine if logged in because 'log_out' link cannot be found for user '%s' with role '%s'", $user->name, $user->role));
      }
      else {
        throw new \Exception(sprintf("Unable to determine if logged in because 'log_out' link cannot be found for user '%s'", $user->name));
      }
    }

    $manager->setCurrentUser($user);
  }

  /**
   * Logs the current user out.
   */
  public function logout() {
    $this->getSession()->visit($this->locatePath('/user/logout'));
    $this->getUserManager()->setCurrentUser(FALSE);
  }

  /**
   * Determine if the a user is already logged in.
   *
   * @return boolean
   *   Returns TRUE if a user is logged in for this session.
   */
  public function loggedIn() {
    $session = $this->getSession();
    $page = $session->getPage();

    // Look for a css selector to determine if a user is logged in.
    // Default is the logged-in class on the body tag.
    // Which should work with almost any theme.
    try {
      if ($page->has('css', $this->getDrupalSelector('logged_in_selector'))) {
        return TRUE;
      }
    } catch (DriverException $e) {
      // This test may fail if the driver did not load any site yet.
    }

    // Some themes do not add that class to the body, so lets check if the
    // login form is displayed on /user/login.
    $session->visit($this->locatePath('/user/login'));
    if (!$page->has('css', $this->getDrupalSelector('login_form_selector'))) {
      return TRUE;
    }

    $session->visit($this->locatePath('/'));

    // As a last resort, if a logout link is found, we are logged in. While not
    // perfect, this is how Drupal SimpleTests currently work as well.
    if ($page->findLink($this->getDrupalText('log_out'))) {
      return TRUE;
    }

    // The user appears to be anonymous. Clear the current user from the user
    // manager so this reflects the actual situation.
    $this->getUserManager()->setCurrentUser(FALSE);
    return FALSE;
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
  public function loggedInWithRole($role) {
    return $this->loggedIn() && $this->getUserManager()->currentUserHasRole($role);
  }

}
