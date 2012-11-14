<?php

namespace Drupal\Driver;

use Drupal\Exception\BootstrapException,
    Drupal\Exception\UnsupportedDriverActionException;
use Symfony\Component\Process\Process;

/**
 * Implements DriverInterface.
 */
class DrushDriver implements DriverInterface {
  /**
   * Store a drush alias for tests requiring shell access.
   */
  public $alias;

  /**
   * Track bootstrapping.
   */
  private $bootstrapped = FALSE;

  /**
   * Set drush alias.
   */
  public function __construct($alias) {
    // Trim off the '@' symbol if it has been added.
    $alias = ltrim($alias, '@');

    $this->alias = $alias;
  }

  /**
   * Implements DriverInterface::bootstrap().
   */
  public function bootstrap() {
    // Check that the given alias works.
    // @todo check that this is a functioning alias.
    // See http://drupal.org/node/1615450
    if (!$this->alias) {
      throw new BootstrapException('A drush alias is required.');
    }
    $this->bootstrapped = TRUE;
  }

  /**
   * Implements DriverInterface::isBootstrapped().
   */
  public function isBootstrapped() {
    return $this->bootstrapped;
  }

  /**
   * Implements DriverInterface::userCreate().
   */
  public function userCreate(\stdClass $user) {
    $arguments = array(
      $user->name,
    );
    $options = array(
      'password' => $user->pass,
      'mail' => $user->mail,
    );
    $this->drush('user-create', $arguments, $options);
  }

  /**
   * Implements DriverInterface::userDelete().
   */
  public function userDelete(\stdClass $user) {
    $arguments = array($user->name);
    $options = array(
      'yes' => NULL,
      'delete-content' => NULL,
    );
    $this->drush('user-cancel', $arguments, $options);
  }

  /**
   * Implements DriverInterface::userAddRole().
   */
  public function userAddRole(\stdClass $user, $role) {
    $arguments = array(
      sprintf('"%s"', $role),
      $user->name
    );
    $this->drush('user-add-role', $arguments);
  }

  /**
   * Implements DriverInterface::fetchWatchdog().
   */
  public function fetchWatchdog($count = 10, $type = NULL, $severity = NULL) {
    $options = array(
      '--count' => $count,
      '--type' => $type,
      '--severity' => $severity,
    );
    return $this->drush('watchdog-show', array(), $options);
  }

  /**
   * Implements DriverInterface::clearCache().
   */
  public function clearCache($type = 'all') {
    $type = array($type);
    return $this->drush('cache-clear', $type, array());
  }


  /**
   * Execute a drush command.
   */
  public function drush($command, array $arguments = array(), array $options = array()) {
    $arguments = implode(' ', $arguments);
    $string_options = '';
    foreach ($options as $name => $value) {
      if (is_null($value)) {
        $string_options .= ' --' . $name;
      }
      else {
        $string_options .= ' --' . $name . '=' . $value;
      }
    }
    $process = new Process("drush @{$this->alias} {$command} {$string_options} {$arguments}");
    $process->setTimeout(3600);
    $process->run();
    if (!$process->isSuccessful()) {
      throw new \RuntimeException($process->getErrorOutput());
    }
    return $process->getOutput();
  }

  /**
   * Implements DriverInterface::createNode().
   */
  public function createNode(\stdClass $node) {
    throw new UnsupportedDriverActionException('No ability to create nodes in %s', $this);
  }
}
