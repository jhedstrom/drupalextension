<?php

namespace Drupal\Driver\Cores;

use Drupal\Exception\BootstrapException;

/**
 * Drupal 6 core.
 */
class Drupal6 implements CoreInterface {
  public $drupalRoot;
  public $uri;

  /**
   * Set drupalRoot.
   */
  public function __construct($drupalRoot, $uri = 'default') {
    $this->drupalRoot = realpath($drupalRoot);
    $this->uri = $uri;
  }

  /**
   * Implements CoreInterface::bootstrap().
   */
  public function bootstrap() {
    // Validate, and prepare environment for Drupal bootstrap.
    if (!defined('DRUPAL_ROOT')) {
      define('DRUPAL_ROOT', $this->drupalRoot);
      require_once DRUPAL_ROOT . '/includes/bootstrap.inc';
      $this->validateDrupalSite();
    }

    // Bootstrap Drupal.
    $current_path = getcwd();
    chdir(DRUPAL_ROOT);
    drupal_bootstrap(DRUPAL_BOOTSTRAP_CONFIGURATION);
    if (empty($GLOBALS['db_url'])) {
      throw new BootstrapException('Missing database setting, verify the database configuration in settings.php.');
    }
    drupal_bootstrap(DRUPAL_BOOTSTRAP_FULL);
    chdir($current_path);
  }

  /**
   * Implements CoreInterface::clearCache().
   */
  public function clearCache() {
    // Need to change into the Drupal root directory or the registry explodes.
    $current_path = getcwd();
    chdir(DRUPAL_ROOT);
    drupal_flush_all_caches();
    chdir($current_path);
  }

  /**
   * Implements CoreInterface::nodeCreate().
   */
  public function nodeCreate($node) {
    // Default status to 1 if not set.
    if (!isset($node->status)) {
      $node->status = 1;
    }
    node_save($node);
    return $node;
  }

  /**
   * Implements CoreInterface::nodeDelete().
   */
  public function nodeDelete($node) {
    node_delete($node->nid);
  }

  /**
   * Implements CoreInterface::runCron().
   */
  public function runCron() {
    return drupal_cron_run();
  }

  /**
   * Implements CoreInterface::userCreate().
   */
  public function userCreate(\stdClass $user) {
    // Default status to TRUE if not explicitly creating a blocked user.
    if (!isset($user->status)) {
      $user->status = 1;
    }

    // Clone user object, otherwise user_save() changes the password to the
    // hashed password.
    $account = clone $user;
    if (!isset($account->uid)) {
      $account->uid = NULL;
    }

    $new_user = user_save($account, (array) $user);

    // Store UID.
    $user->uid = $new_user->uid;
  }

  /**
   * Implements CoreInterface::userDelete().
   */
  public function userDelete(\stdClass $user) {
//    user_delete(array(), $user->uid);
  }

  /**
   * Implements CoreInterface::userAddRole().
   */
  public function userAddRole(\stdClass $user, $role_name) {
    $result = db_fetch_array(db_query("SELECT rid FROM {role} WHERE name = '%s'", $role_name));

    if (!$result) {
      throw new \RuntimeException(sprintf('No role "%s" exists.', $role_name));
    }

    user_multiple_role_edit(array($user->uid), 'add_role', $result['rid']);
  }

  /**
   * Implements CoreInterface::validateDrupalSite().
   */
  public function validateDrupalSite() {
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
    $drushrc_file = $this->drupalRoot . "/$conf_path/drushrc.php";
    if (file_exists($drushrc_file)) {
      require_once $drushrc_file;
    }
  }

  /**
   * Implements CoreInterface::termCreate.
   * @todo implement for drupal6
   */
  public function termCreate(\stdClass $term) {
    throw new UnsupportedDriverActionException(
      'No ability to create create terms in %s', $this
    );
  }

  /**
   * Implements CoreInterface::termDelete.
   * @todo implement for drupal6
   */
  public function termDelete(\stdClass $term) {
    throw new UnsupportedDriverActionException(
      'No ability to delete terms in %s', $this
    );
  }

  /**
   * Implements CoreInterface::roleCreate().
   */
  public function roleCreate(array $permissions) {
    // TODO: fill in.
    throw new UnsupportedDriverActionException('No ability to create roles in %s', $this);
    //db_query("INSERT INTO {permission} (rid, perm) VALUES (%d, '%s')", $role->rid, implode(', ', array_keys($form_state['values'][$role->rid])));
  }

  /**
   * Implements CoreInterface::roleDelete().
   */
  public function roleDelete($rid) {
    db_query('DELETE FROM {role} WHERE rid = %d', $rid);

    if (!db_affected_rows()) {
      throw new \RuntimeException(sprintf('No role "%s" exists.', $rid));
    }
  }

}
