<?php

declare(strict_types=1);

namespace Drupal\DrupalExtension\Manager;

use Drupal\Driver\Entity\EntityStubInterface;

/**
 * Interface for classes that authenticate users during tests.
 */
interface DrupalAuthenticationManagerInterface {

  /**
   * Logs in as the given user.
   *
   * @param \Drupal\Driver\Entity\EntityStubInterface $user
   *   The user stub to log in.
   */
  public function logIn(EntityStubInterface $user): void;

  /**
   * Logs the current user out.
   */
  public function logOut(): void;

  /**
   * Determine if a user is already logged in.
   *
   * @return bool
   *   Returns TRUE if a user is logged in for this session.
   *
   * @todo v6: Rename to isLoggedIn().
   */
  public function loggedIn();

}
