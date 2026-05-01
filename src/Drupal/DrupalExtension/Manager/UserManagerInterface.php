<?php

declare(strict_types=1);

namespace Drupal\DrupalExtension\Manager;

use Drupal\Driver\Entity\EntityStubInterface;

/**
 * Interface for classes that manage users created during tests.
 */
interface UserManagerInterface {

  /**
   * Returns the currently logged in user.
   *
   * @return \Drupal\Driver\Entity\EntityStubInterface|false
   *   The user stub, or FALSE if the user is anonymous.
   */
  public function getCurrentUser(): EntityStubInterface|false;

  /**
   * Sets the currently logged in user.
   *
   * @param \Drupal\Driver\Entity\EntityStubInterface|false $user
   *   The user stub, or FALSE if the user has been logged out.
   */
  public function setCurrentUser(EntityStubInterface|false $user): void;

  /**
   * Adds a new user.
   *
   * Call this after creating a new user to keep track of all the users that are
   * created in a test scenario. They can then be cleaned up after completing
   * the test.
   *
   * @param \Drupal\Driver\Entity\EntityStubInterface $user
   *   The user stub.
   *
   * @see \Drupal\DrupalExtension\Context\RawDrupalContext::cleanUsers()
   */
  public function addUser(EntityStubInterface $user): void;

  /**
   * Removes a user from the list of users that were created in the test.
   *
   * @param string $userName
   *   The name of the user to remove.
   */
  public function removeUser(string $userName): void;

  /**
   * Returns the list of users that were created in the test.
   *
   * @return array<string, \Drupal\Driver\Entity\EntityStubInterface>
   *   An array of user stubs keyed by user name.
   */
  public function getUsers(): array;

  /**
   * Returns the user with the given user name.
   *
   * @param string $userName
   *   The name of the user to return.
   *
   * @return \Drupal\Driver\Entity\EntityStubInterface
   *   The user stub.
   *
   * @throws \InvalidArgumentException
   *   Thrown when the user with the given name does not exist.
   */
  public function getUser(string $userName): EntityStubInterface;

  /**
   * Clears the list of users that were created in the test.
   */
  public function clearUsers(): void;

  /**
   * Returns whether or not any users were created in the test.
   *
   * @return bool
   *   TRUE if any users are tracked, FALSE if not.
   */
  public function hasUsers(): bool;

  /**
   * Returns whether the current user is anonymous.
   *
   * @return bool
   *   TRUE if the current user is anonymous.
   */
  public function currentUserIsAnonymous(): bool;

  /**
   * Checks if the current user has the given role(s)
   *
   * @param string $role
   *   A single role, or multiple comma-separated roles in a single string.
   *
   * @return bool
   *   Returns TRUE if the currently logged in user has this role (or roles).
   */
  public function currentUserHasRole(string $role): bool;

}
