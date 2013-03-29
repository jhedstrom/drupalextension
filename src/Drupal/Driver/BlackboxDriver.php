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

  public function processBatch() {
    throw new UnsupportedDriverActionException('No ability to process batch actions in %s', $this);
  }
  /**
   * Implements DriverInterface::userAddRole().
   */
  public function userAddRole(\stdClass $user, $role) {
    throw new UnsupportedDriverActionException('No ability to add roles for a user in %s', $this);
  }

  /**
   * Implements DriverInterface::fetchWatchdog().
   */
  public function fetchWatchdog($count = 10, $type = NULL, $severity = NULL) {
    throw new UnsupportedDriverActionException('No ability to access watchdog entries in %s', $this);
  }

  /**
   * Implements DriverInterface::clearCache().
   */
  public function clearCache($type = NULL) {
    throw new UnsupportedDriverActionException('No ability to clear Drupal caches in %s', $this);
  }

  /**
   * Implements DriverInterface::createNode().
   */
  public function createNode(\stdClass $node) {
    throw new UnsupportedDriverActionException('No ability to create nodes in %s', $this);
  }

  /**
   * Implements DriverInterface::nodeDelete().
   */
  public function nodeDelete(\stdClass $node) {
    throw new UnsupportedDriverActionException('No ability to delete nodes in %s', $this);
  }

  /**
   * Implements DriverInterface::runCron().
   */
  public function runCron() {
    throw new UnsupportedDriverActionException('No ability to run cron in %s', $this);
  }

  /**
   * Implements DriverInterface::createTerm().
   */
  public function createTerm(\stdClass $term) {
    throw new UnsupportedDriverActionException('No ability to create terms in %s', $this);
  }
}

