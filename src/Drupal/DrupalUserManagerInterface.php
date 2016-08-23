<?php

namespace Drupal;

/**
 * Interface for classes that manage the currently logged in user.
 */
interface DrupalUserManagerInterface {

  /**
   * Returns the currently logged in user.
   *
   * A value of FALSE denotes an anonymous user.
   *
   * @return \stdClass|bool
   */
  public function getUser();

  /**
   * Sets the currently logged in user.
   *
   * @param \stdClass|bool $user
   */
  public function setUser($user);

}
