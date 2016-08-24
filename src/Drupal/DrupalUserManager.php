<?php

namespace Drupal;

/**
 * Default implementation of the Drupal user manager service.
 */
class DrupalUserManager implements DrupalUserManagerInterface {

  /**
   * The user object representing the currently logged in user.
   *
   * @var \stdClass|FALSE
   */
  protected $user = FALSE;

  /**
   * An array of user objects representing users created during the test.
   *
   * @var \stdClass[]
   */
  protected $users = [];

  /**
   * {@inheritdoc}
   */
  public function getCurrentUser() {
    return $this->user;
  }

  /**
   * {@inheritdoc}
   */
  public function setCurrentUser($user) {
    $this->user = $user;
  }

  /**
   * {@inheritdoc}
   */
  public function addUser($user) {
    $this->users[$user->name] = $user;
  }

  /**
   * {@inheritdoc}
   */
  public function removeUser($userName) {
    unset($this->users[$userName]);
  }

  /**
   * {@inheritdoc}
   */
  public function getUser($userName) {
    if (!isset($this->users[$userName])) {
      throw new \Exception(sprintf('No user with %s name is registered with the driver.', $userName));
    }
    return $this->users[$userName];
  }

  /**
   * {@inheritdoc}
   */
  public function getUsers() {
    return $this->users;
  }

  /**
   * {@inheritdoc}
   */
  public function clearUsers() {
    $this->user = FALSE;
    $this->users = [];
  }

  /**
   * {@inheritdoc}
   */
  public function hasUsers() {
    return !empty($this->users);
  }

  /**
   * {@inheritdoc}
   */
  public function currentUserIsAnonymous() {
    return empty($this->user);
  }

  /**
   * {@inheritdoc}
   */
  public function currentUserHasRole($role) {
    return !$this->currentUserIsAnonymous() && !empty($this->user->role) && $this->user->role == $role;
  }

}
