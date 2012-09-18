<?php

namespace Drupal\Driver;

use Drupal\Exception\UnsupportedDriverActionException;

/**
 * Implements DriverInterface.
 */
class BlackboxDriver implements DriverInterface {

  /**
   * Implements DriverInterface::bootstrap().
   */
  public function bootstrap() {
    // Nothing to do here.
  }

  /**
   * Implements DriverInterface::isBootstrapped().
   */
  public function isBootstrapped() {
    // Assume the blackbox is always bootstrapped.
    return TRUE;
  }

  /**
   * Implements DriverInterface::userCreate().
   */
  public function userCreate(\stdClass $user) {
    throw new UnsupportedDriverActionException('No ability to create users in %s', $this);
  }

  /**
   * Implements DriverInterface::userDelete().
   */
  public function userDelete(\stdClass $user) {
    throw new UnsupportedDriverActionException('No ability to delete users in %s', $this);
  }

  /**
   * Implements DriverInterface::userAddRole().
   */
  public function userAddRole(\stdClass $user, $role) {
    throw new UnsupportedDriverActionException('No ability to add roles for a user in %s', $this);
  }
}
