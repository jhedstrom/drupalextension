<?php

declare(strict_types=1);

namespace Drupal\DrupalExtension\Manager;

/**
 * Default implementation of the Drupal user manager service.
 */
class DrupalUserManager implements DrupalUserManagerInterface {

  /**
   * The user object representing the currently logged in user.
   *
   * @var \stdClass|false
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
  public function setCurrentUser(\stdClass|bool $user): void {
    $this->user = $user;
  }

  /**
   * {@inheritdoc}
   */
  public function addUser(\stdClass $user): void {
    $this->users[$user->name] = $user;
  }

  /**
   * {@inheritdoc}
   */
  public function removeUser(string $userName): void {
    unset($this->users[$userName]);
  }

  /**
   * {@inheritdoc}
   */
  public function getUser(string $userName) {
    if (!isset($this->users[$userName])) {
      throw new \InvalidArgumentException(sprintf('No user with %s name is registered with the driver.', $userName));
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
  public function clearUsers(): void {
    $this->user = FALSE;
    $this->users = [];
  }

  /**
   * {@inheritdoc}
   */
  public function hasUsers(): bool {
    return !empty($this->users);
  }

  /**
   * {@inheritdoc}
   */
  public function currentUserIsAnonymous(): bool {
    return empty($this->user);
  }

  /**
   * {@inheritdoc}
   */
  public function currentUserHasRole(string $role): bool {
    return !$this->currentUserIsAnonymous() && !empty($this->user->role) && $this->user->role == $role;
  }

}
