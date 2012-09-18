<?php

namespace Drupal\Driver;

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
   * Set drush alias.
   */
  public function __construct($alias) {
    // Trim off the '@' symbol if it has been added.
    $alias = ltrim($alias, '@');

    $this->alias = $alias;
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
      $user->name,
      $role,
    );
    $this->drush('user-add-role', $arguments);
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
  }
}
