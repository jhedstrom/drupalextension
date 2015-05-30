<?php

namespace Drupal\DrupalExtension\Context;

use Behat\MinkExtension\Context\RawMinkContext;
use Behat\Testwork\Hook\HookDispatcher;

use Drupal\DrupalDriverManager;

use Drupal\DrupalExtension\Hook\Scope\AfterNodeCreateScope;
use Drupal\DrupalExtension\Hook\Scope\AfterTermCreateScope;
use Drupal\DrupalExtension\Hook\Scope\AfterUserCreateScope;
use Drupal\DrupalExtension\Hook\Scope\BaseEntityScope;
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
   * Keep track of nodes so they can be cleaned up.
   *
   * @var array
   */
  protected $nodes = array();

  /**
   * Current authenticated user.
   *
   * A value of FALSE denotes an anonymous user.
   *
   * @var stdClass|bool
   */
  public $user = FALSE;

  /**
   * Keep track of all users that are created so they can easily be removed.
   *
   * @var array
   */
  protected $users = array();

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
   * Get active Drupal Driver.
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
    if (!empty($this->users)) {
      foreach ($this->users as $user) {
        $this->getDriver()->userDelete($user);
      }
      $this->getDriver()->processBatch();
      $this->users = array();
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
   * Dispatch scope hooks.
   *
   * @param string $scope
   *   The entity scope to dispatch.
   * @param stdClass $entity
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
   * @param $entity_type
   * @param $entity
   */
  public function parseEntityFields($entity_type, $entity) {
    foreach ($entity as $field_name => $value) {
      if ($this->getDriver()->isField($entity_type, $field_name)) {
        $values = explode(', ', $value);
        foreach ($values as $key => $value) {
          if (strstr($value, ' - ') !== FALSE) {
            $values[$key] = explode(' - ', $value);
          }
        }
        $entity->$field_name = $values;
      }
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
    $this->users[$user->name] = $this->user = $user;
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
   * Log-in the current user.
   */
  public function login() {
    // Check if logged in.
    if ($this->loggedIn()) {
      $this->logout();
    }

    if (!$this->user) {
      throw new \Exception('Tried to login without a user.');
    }

    $this->getSession()->visit($this->locatePath('/user'));
    $element = $this->getSession()->getPage();
    $element->fillField($this->getDrupalText('username_field'), $this->user->name);
    $element->fillField($this->getDrupalText('password_field'), $this->user->pass);
    $submit = $element->findButton($this->getDrupalText('log_in'));
    if (empty($submit)) {
      throw new \Exception(sprintf("No submit button at %s", $this->getSession()->getCurrentUrl()));
    }

    // Log in.
    $submit->click();

    if (!$this->loggedIn()) {
      throw new \Exception(sprintf("Failed to log in as user '%s' with role '%s'", $this->user->name, $this->user->role));
    }
  }

  /**
   * Logs the current user out.
   */
  public function logout() {
    $this->getSession()->visit($this->locatePath('/user/logout'));
  }

  /**
   * Determine if the a user is already logged in.
   *
   * @return boolean
   *   Returns TRUE if a user is logged in for this session.
   */
  public function loggedIn() {
    $session = $this->getSession();
    $session->visit($this->locatePath('/'));

    // If a logout link is found, we are logged in. While not perfect, this is
    // how Drupal SimpleTests currently work as well.
    $element = $session->getPage();
    return $element->findLink($this->getDrupalText('log_out'));
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
    return $this->loggedIn() && $this->user && isset($this->user->role) && $this->user->role == $role;
  }

}
