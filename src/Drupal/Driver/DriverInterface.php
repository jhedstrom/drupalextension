<?php

namespace Drupal\Driver;

/**
 * Driver interface.
 */
interface DriverInterface {

  /**
   * Bootstrap operations, as needed.
   */
  public function bootstrap();

  /**
   * Determine if the driver has been bootstrapped.
   */
  public function isBootstrapped();

  /**
   * Create a user.
   */
  public function userCreate(\stdClass $user);

  /**
   * Delete a user.
   */
  public function userDelete(\stdClass $user);

  /**
   * Add a role for a user.
   *
   * @param stdClass $user
   *   A user object.
   * @param string $role
   *   The role name to assign.
   */
  public function userAddRole(\stdClass $user, $role);
}
