<?php

namespace Drupal\DrupalExtension\Manager;

/**
 * Interface for classes that manage users created during tests.
 */
interface DrupalUserManagerInterface
{

  /**
   * Returns the currently logged in user.
   *
   * @return \stdClass|bool
   *   The user object, or FALSE if the user is anonymous.
   */
    public function getCurrentUser();

  /**
   * Sets the currently logged in user.
   *
   * @param \stdClass|bool $user
   *   The user object, or FALSE if the user has been logged out.
   */
    public function setCurrentUser($user);

  /**
   * Adds a new user.
   *
   * Call this after creating a new user to keep track of all the users that are
   * created in a test scenario. They can then be cleaned up after completing
   * the test.
   *
   * @see \Drupal\DrupalExtension\Context\RawDrupalContext::cleanUsers()
   *
   * @param \stdClass
   *   The user object.
   */
    public function addUser($user);

  /**
   * Removes a user from the list of users that were created in the test.
   *
   * @param $userName
   *   The name of the user to remove.
   */
    public function removeUser($userName);

  /**
   * Returns the list of users that were created in the test.
   *
   * @return \stdClass[]
   *   An array of user objects.
   */
    public function getUsers();

  /**
   * Returns the user with the given user name.
   *
   * @param string $userName
   *   The name of the user to return.
   *
   * @return \stdClass
   *   The user object.
   *
   * @throws \InvalidArgumentException
   *   Thrown when the user with the given name does not exist.
   */
    public function getUser($userName);

  /**
   * Clears the list of users that were created in the test.
   */
    public function clearUsers();

  /**
   * Returns whether or not any users were created in the test.
   *
   * @return bool
   *   TRUE if any users are tracked, FALSE if not.
   */
    public function hasUsers();

  /**
   * Returns whether the current user is anonymous.
   *
   * @return bool
   *   TRUE if the current user is anonymous.
   */
    public function currentUserIsAnonymous();

  /**
   * Checks if the current user has the given role(s)
   *
   * @param string $role
   *   A single role, or multiple comma-separated roles in a single string.
   *
   * @return boolean
   *   Returns TRUE if the currently logged in user has this role (or roles).
   */
    public function currentUserHasRole($role);
}
