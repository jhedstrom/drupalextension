<?php

namespace Drupal\Driver;

use Drupal\Exception\UnsupportedDriverActionException;

/**
 * Implements DriverInterface.
 */
abstract class BaseDriver implements DriverInterface {

  /**
   * Implements DriverInterface::bootstrap().
   */
  public function bootstrap() {
  }

  /**
   * Implements DriverInterface::isBootstrapped().
   */
  public function isBootstrapped() {
  }

  /**
   * Implements DriverInterface::userCreate().
   */
  public function userCreate(\stdClass $user) {
    throw new UnsupportedDriverActionException($this->errorString('create users'), $this);
  }

  /**
   * Implements DriverInterface::userDelete().
   */
  public function userDelete(\stdClass $user) {
    throw new UnsupportedDriverActionException($this->errorString('delete users'), $this);
  }

  public function processBatch() {
    throw new UnsupportedDriverActionException($this->errorString('process batch actions'), $this);
  }
  /**
   * Implements DriverInterface::userAddRole().
   */
  public function userAddRole(\stdClass $user, $role) {
    throw new UnsupportedDriverActionException($this->errorString('add roles'), $this);
  }

  /**
   * Implements DriverInterface::fetchWatchdog().
   */
  public function fetchWatchdog($count = 10, $type = NULL, $severity = NULL) {
    throw new UnsupportedDriverActionException($this->errorString('access watchdog entries'), $this);
  }

  /**
   * Implements DriverInterface::clearCache().
   */
  public function clearCache($type = NULL) {
    throw new UnsupportedDriverActionException($this->errorString('clear Drupal caches'), $this);
  }

  /**
   * Implements DriverInterface::createNode().
   */
  public function createNode($node) {
    throw new UnsupportedDriverActionException($this->errorString('create nodes'), $this);
  }

  /**
   * Implements DriverInterface::nodeDelete().
   */
  public function nodeDelete($node) {
    throw new UnsupportedDriverActionException($this->errorString('delete nodes'), $this);
  }

  /**
   * Implements DriverInterface::runCron().
   */
  public function runCron() {
    throw new UnsupportedDriverActionException($this->errorString('run cron'), $this);
  }

  /**
   * Implements DriverInterface::createTerm().
   */
  public function createTerm(\stdClass $term) {
    throw new UnsupportedDriverActionException($this->errorString('create terms'), $this);
  }

  /**
   * Implements DriverInterface::termDelete().
   */
  public function termDelete(\stdClass $term) {
    throw new UnsupportedDriverActionException($this->errorString('delete terms'), $this);
  }

  /**
   * Implements DriverInterface::roleCreate().
   */
  public function roleCreate(array $permissions) {
    throw new UnsupportedDriverActionException($this->errorString('create roles'), $this);
  }

  /**
   * Implements DriverInterface::roleDelete().
   */
  public function roleDelete($rid) {
    throw new UnsupportedDriverActionException($this->errorString('delete roles'), $this);
  }

  /**
   * Error printing exception
   *
   * @param string $error
   *   The term, node, user or permission.
   *
   * @return String
   *   A formatted string reminding people to use an api driver.
   */
  private function errorString($error) {
    return sprintf('No ability to %s in %%s. Put `@api` into your feature and add an api driver (ex: `api_driver: drupal`) in behat.yml.', $error);
  }
}
