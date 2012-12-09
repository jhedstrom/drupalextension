<?php

namespace Drupal\Driver;

use Drupal\Exception\BootstrapException,
    Drupal\Exception\UnsupportedDriverActionException,
    Drupal\DrupalExtension\Context\DrupalSubContextFinderInterface;

/**
 * Fully bootstraps Drupal and uses native API calls.
 */
class DrupalDriver implements DriverInterface, DrupalSubContextFinderInterface {
  private $drupalRoot;
  private $uri;
  private $bootstrapped = FALSE;

  /**
   * Set Drupal root and URI.
   */
  public function __construct($drupalRoot, $uri) {
    $this->drupalRoot = realpath($drupalRoot);
    $this->uri = $uri;
  }

  /**
   * Implements DriverInterface::bootstrap().
   */
  public function bootstrap() {
    // Validate, and prepare environment for Drupal bootstrap.
    define('DRUPAL_ROOT', $this->drupalRoot);
    require_once DRUPAL_ROOT . '/includes/bootstrap.inc';
    $this->validateDrupalSite();

    // Bootstrap Drupal.
    chdir(DRUPAL_ROOT);
    drupal_bootstrap(DRUPAL_BOOTSTRAP_FULL);
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
    // Default status to TRUE if not explicitly creating a blocked user.
    if (!isset($user->status)) {
      $user->status = 1;
    }

    // Clone user object, otherwise user_save() changes the password to the
    // hashed password.
    $account = clone $user;

    \user_save($account, (array) $user);

    // Store UID.
    $user->uid = $account->uid;
  }

  /**
   * Implements DriverInterface::userDelete().
   */
  public function userDelete(\stdClass $user) {
    \user_cancel(array(), $user->uid, 'user_cancel_delete');
  }

  /**
   * Implements DriverInterface::userAddRole().
   */
  public function userAddRole(\stdClass $user, $role_name) {
    $role = \user_role_load_by_name($role_name);

    if (!$role) {
      throw new \RuntimeException(sprintf('No role "%s" exists.', $role_name));
    }

    \user_multiple_role_edit(array($user->uid), 'add_role', $role->rid);
  }

  /**
   * Implements DriverInterface::fetchWatchdog().
   */
  public function fetchWatchdog($count = 10, $type = NULL, $severity = NULL) {
    // @todo
    throw new UnsupportedDriverActionException('No ability to access watchdog entries in %s', $this);
  }

  /**
   * Implements DriverInterface::clearCache().
   */
  public function clearCache($type = NULL) {
    // Need to change into the Drupal root directory or the registry explodes.
    $current_path = getcwd();
    chdir(DRUPAL_ROOT);
    \drupal_flush_all_caches();
    chdir($current_path);
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
   * Validate, and prepare environment for Drupal bootstrap.
   *
   * @throws BootstrapException
   *
   * @see _drush_bootstrap_drupal_site_validate()
   */
  private function validateDrupalSite() {
    if ('default' !== $this->uri) {
      // Fake the necessary HTTP headers that Drupal needs:
      $drupal_base_url = parse_url($this->uri);
      // If there's no url scheme set, add http:// and re-parse the url
      // so the host and path values are set accurately.
      if (!array_key_exists('scheme', $drupal_base_url)) {
        $drush_uri = 'http://' . $this->uri;
        $drupal_base_url = parse_url($this->uri);
      }
      // Fill in defaults.
      $drupal_base_url += array(
        'path' => NULL,
        'host' => NULL,
        'port' => NULL,
      );
      $_SERVER['HTTP_HOST'] = $drupal_base_url['host'];

      if ($drupal_base_url['port']) {
        $_SERVER['HTTP_HOST'] .= ':' . $drupal_base_url['port'];
      }
      $_SERVER['SERVER_PORT'] = $drupal_base_url['port'];

      if (array_key_exists('path', $drupal_base_url)) {
        $_SERVER['PHP_SELF'] = $drupal_base_url['path'] . '/index.php';
      }
      else {
        $_SERVER['PHP_SELF'] = '/index.php';
      }
    }
    else {
      $_SERVER['HTTP_HOST'] = 'default';
      $_SERVER['PHP_SELF'] = '/index.php';
    }

    $_SERVER['REQUEST_URI'] = $_SERVER['SCRIPT_NAME'] = $_SERVER['PHP_SELF'];
    $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
    $_SERVER['REQUEST_METHOD']  = NULL;

    $_SERVER['SERVER_SOFTWARE'] = NULL;
    $_SERVER['HTTP_USER_AGENT'] = NULL;

    $conf_path = conf_path(TRUE, TRUE);
    $conf_file = $this->drupalRoot . "/$conf_path/settings.php";
    if (!file_exists($conf_file)) {
      throw new BootstrapException(sprintf('Could not find a Drupal settings.php file at "%s"', $conf_file));
    }
  }

  /**
   * Implements DriverInterface::createNode().
   */
  public function createNode(\stdClass $node) {
    // Default status to 1 if not set.
    if (!isset($node->status)) {
      $node->status = 1;
    }
    \node_save($node);
    return $node;
  }
}
