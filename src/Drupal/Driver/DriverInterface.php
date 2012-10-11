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

  /**
   * Retrieve watchdog entries.
   *
   * @param integer $count
   *   Number of entries to retrieve.
   * @param string $type
   *   Filter by watchdog type.
   * @param string $severity
   *   Filter by watchdog severity level.
   *
   * @return string
   *   Watchdog output.
   */
  public function fetchWatchdog($count = 10, $type = NULL, $severity = NULL);
}
