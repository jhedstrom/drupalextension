<?php

namespace Drupal\Driver;

use Drupal\Exception\BootstrapException,
    Drupal\DrupalExtension\Context\DrupalSubContextFinderInterface;

use Behat\Behat\Exception\PendingException;

/**
 * Fully bootstraps Drupal and uses native API calls.
 */
class DrupalDriver implements DriverInterface, DrupalSubContextFinderInterface {
  private $bootstrapped = FALSE;
  public  $core;
  public  $version;

  /**
   * Set Drupal root and URI.
   */
  public function __construct($drupalRoot, $uri) {
    $this->drupalRoot = realpath($drupalRoot);
    $this->uri = $uri;
    $this->version = $this->getDrupalVersion();
  }

  /**
   * Implements DriverInterface::bootstrap().
   */
  public function bootstrap() {
    $this->getCore()->bootstrap();
    $this->bootstrapped = TRUE;
  }

  /**
   * Implements DriverInterface::isBootstrapped().
   */
  public function isBootstrapped() {
    // Assume the blackbox is always bootstrapped.
    return $this->bootstrapped;
  }

  /**
   * Implements DriverInterface::userCreate().
   */
  public function userCreate(\stdClass $user) {
    $this->getCore()->userCreate($user);
  }

  /**
   * Implements DriverInterface::userDelete().
   */
  public function userDelete(\stdClass $user) {
    $this->getCore()->userDelete($user);
  }

  public function processBatch() {
    $this->getCore()->processBatch();
  }
  /**
   * Implements DriverInterface::userAddRole().
   */
  public function userAddRole(\stdClass $user, $role_name) {
    $this->getCore()->userAddRole($user, $role_name);
  }

  /**
   * Implements DriverInterface::fetchWatchdog().
   */
  public function fetchWatchdog($count = 10, $type = NULL, $severity = NULL) {
    throw new PendingException(sprintf('Currently no ability to access watchdog entries in %s', $this));
  }

  /**
   * Implements DriverInterface::clearCache().
   */
  public function clearCache($type = NULL) {
    $this->getCore()->clearCache();
  }

  /**
   * Implements DrupalSubContextFinderInterface::getPaths().
   */
  public function getSubContextPaths() {
    // Ensure system is bootstrapped.
    if (!$this->isBootstrapped()) {
      $this->bootstrap();
    }

    $paths = array();

    // Get enabled modules.
    $modules = \module_list();
    $paths = array();
    foreach ($modules as $module) {
      $paths[] = $this->drupalRoot . DIRECTORY_SEPARATOR . \drupal_get_path('module', $module);
    }

    // Themes.
    // @todo

    // Active profile
    // @todo

    return $paths;
  }

  /**
   * Determine major Drupal version.
   *
   * @throws BootstrapException
   *
   * @see drush_drupal_version()
   */
  function getDrupalVersion() {
    if (!isset($this->drupalVersion)) {
      // Support 6, 7 and 8.
      $version_constant_paths = array(
        // Drupal 6.
        '/modules/system/system.module',
        // Drupal 7.
        '/includes/bootstrap.inc',
        // Drupal 8.
        '/core/includes/bootstrap.inc',
      );
      foreach ($version_constant_paths as $path) {
        if (file_exists($this->drupalRoot . $path)) {
          require_once $this->drupalRoot . $path;
        }
      }
      if (defined('VERSION')) {
        $version = VERSION;
      }
      else {
        throw new BootstrapException('Unable to determine Drupal core version. Supported versions are 6, 7, and 8.');
      }

      // Extract the major version from VERSION.
      $version_parts = explode('.', $version);
      if (is_numeric($version_parts[0])) {
        $this->drupalVersion = (integer) $version_parts[0];
      }
      else {
        throw new BootstrapException(sprintf('Unable to extract major Drupal core version from version string %s.', $version));
      }
    }
    return $this->drupalVersion;
  }

  /**
   * Instantiate and set Drupal core class.
   *
   * @param array $availableCores
   *   A major-version-keyed array of available core controllers.
   */
  public function setCore($availableCores) {
    if (!isset($availableCores[$this->version])) {
      throw new BootstrapException(sprintf('There is no available Drupal core controller for Drupal version %s.', $this->version));
    }
    $this->core = $availableCores[$this->version];
  }

  /**
   * Return current core.
   */
  public function getCore() {
    return $this->core;
  }

  /**
   * Implements DriverInterface::createNode().
   */
  public function createNode(\stdClass $node) {
    return $this->getCore()->nodeCreate($node);
  }

  /**
   * Implements DriverInterface::runCron().
   */
  public function runCron() {
    if (!$this->getCore()->runCron()) {
      throw new \Exception('Failed to run cron.');
    }
  }

  /**
   * Implements DriverInterface::createTerm().
   */
  public function createTerm(\stdClass $term) {
    if (!isset($term->vid)) {
      // Try to load vocabulary by machine name.
      $vocabularies = \taxonomy_vocabulary_load_multiple(FALSE, array('machine_name' => $term->vocabulary_machine_name));
      if (!empty($vocabularies)) {
        $vids = array_keys($vocabularies);
        $term->vid = reset($vids);
      }
    }

    \taxonomy_term_save($term);
    return $term;
  }
}
