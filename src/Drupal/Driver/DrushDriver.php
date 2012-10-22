<?php

namespace Drupal\Driver;

use Drupal\Exception\BootstrapException,
    Drupal\Exception\UnsupportedDriverActionException,
    Drupal\DrupalExtension\Context\DrupalSubContextFinderInterface;

use Symfony\Component\Process\Process;

/**
 * Implements DriverInterface.
 */
class DrushDriver implements DriverInterface, DrupalSubContextFinderInterface {
  /**
   * Store a drush alias for tests requiring shell access.
   */
  public $alias = FALSE;

  /**
   * Store the root path to a Drupal installation. This is an alternative to
   * using drush aliases.
   */
  public $root = FALSE;

  /**
   * Track bootstrapping.
   */
  private $bootstrapped = FALSE;

  /**
   * Set drush alias or root path.
   *
   * @param string $alias
   *   A drush alias
   * @param string $root_path
   *   The root path of the Drupal install. This is an alternative to using aliases.
   */
  public function __construct($alias = NULL, $root_path = NULL) {
    if ($alias) {
    // Trim off the '@' symbol if it has been added.
    $alias = ltrim($alias, '@');

    $this->alias = $alias;
    }
    elseif ($root_path) {
      $this->root = $root_path;
    }
    else {
      throw new \BootstrapException('A drush alias or root path is required.');
    }
  }

  /**
   * Implements DriverInterface::bootstrap().
   */
  public function bootstrap() {
    // Check that the given alias works.
    // @todo check that this is a functioning alias.
    // See http://drupal.org/node/1615450
    if (!$this->alias && !$this->root) {
      throw new BootstrapException('A drush alias or root path is required.');
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
   * Implements DrupalSubContextFinderInterface::getPaths().
   */
  public function getSubContextPaths() {
    $paths = array();
    // @todo should only return paths if they are local to the machine.

    // Get a list of enabled projects.
    $options = array(
      'status' => 'enabled',
      'pipe' => NULL,
    );
    if ($projects = $this->drush('pm-list', array(), $options)) {
      $projects = explode(PHP_EOL, trim($projects, PHP_EOL));
      // @todo it would be nice if the drush pm-list command had an option to
      // return this info. In the meantime, brute-force query it.
      $query = '"' . sprintf("SELECT filename FROM {system} WHERE name in ('%s')", implode("', '", $projects)) . '"';
      $options = array('db-prefix' => NULL);
      $result = $this->drush('sql-query', array($query), $options);
      $result = explode(PHP_EOL, trim($result, PHP_EOL));

      // Remove SQL header.
      array_shift($result);

      // Strip off module filename and add base path.
      $base_path = $this->getDrupalRoot();

      foreach ($result as $path) {
        $paths[] = dirname($base_path . DIRECTORY_SEPARATOR . $path);
      }
    }

    return $paths;
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

    $alias = $this->alias ? "@{$this->alias}" : '--root=' . $this->root;

    $process = new Process("drush {$alias} {$command} {$string_options} {$arguments}");
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

  /**
   * Helper function to derive the Drupal root directory from given alias.
   */
  public function getDrupalRoot($alias = NULL) {
    if (!$alias) {
      $alias = $this->alias;
    }

    // Use drush site-alias to find path.
    $path = $this->drush('site-alias', array('@' . $alias), array('pipe' => NULL));

    // Remove anything past the # that occasionally returns with site-alias.
    $path = reset(explode('#', $path));

    return $path;
  }
}
