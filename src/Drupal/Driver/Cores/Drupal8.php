<?php

namespace Drupal\Driver\Cores;

use Drupal\Component\Utility\Random;
use Drupal\Exception\BootstrapException;
use Drupal\node\Entity\Node;
use Drupal\node\NodeInterface;
use Drupal\taxonomy\Entity\Term;

/**
 * Drupal 8 core.
 */
class Drupal8 implements CoreInterface {
  /**
   * System path to the Drupal installation.
   *
   * @var string
   */
  private $drupalRoot;

  /**
   * URI for the Drupal installation.
   *
   * @var string
   */
  private $uri;

  /**
   * Random generator.
   *
   * @var \Drupal\Component\Utility\Random
   */
  private $random;

  /**
   * {@inheritDoc}
   */
  public function __construct($drupalRoot, $uri = 'default', Random $random) {
    $this->drupalRoot = realpath($drupalRoot);
    $this->uri = $uri;
    $this->random = $random;
  }

  /**
   * {@inheritDoc}
   */
  public function bootstrap() {
    // Validate, and prepare environment for Drupal bootstrap.
    if (!defined('DRUPAL_ROOT')) {
      define('DRUPAL_ROOT', $this->drupalRoot);
    }

    // Bootstrap Drupal.
    $current_path = getcwd();
    chdir(DRUPAL_ROOT);
    require_once DRUPAL_ROOT . '/core/includes/bootstrap.inc';
    $this->validateDrupalSite();

    drupal_bootstrap(DRUPAL_BOOTSTRAP_FULL);

    // Initialise an anonymous session. required for the bootstrap.
    \Drupal::service('session_manager')->start();

    chdir($current_path);
  }

  /**
   * {@inheritDoc}
   */
  public function clearCache() {
    // Need to change into the Drupal root directory or the registry explodes.
    $current_path = getcwd();
    chdir(DRUPAL_ROOT);
    drupal_flush_all_caches();
    chdir($current_path);
  }

  /**
   * {@inheritDoc}
   */
  public function nodeCreate($node) {
    // Default status to 1 if not set.
    if (!isset($node->status)) {
      $node->status = 1;
    }
    $entity = entity_create('node', (array) $node);
    $entity->save();

    $node->nid = $entity->id();

    return $node;
  }

  /**
   * {@inheritDoc}
   */
  public function nodeDelete($node) {
    $node = $node instanceof NodeInterface ? $node : Node::load($node->nid);
    $node->delete();
  }

  /**
   * {@inheritDoc}
   */
  public function runCron() {
    return \Drupal::service('cron')->run();
  }

  /**
   * {@inheritDoc}
   */
  public function userCreate(\stdClass $user) {
    $this->validateDrupalSite();

    // Default status to TRUE if not explicitly creating a blocked user.
    if (!isset($user->status)) {
      $user->status = 1;
    }

    // Clone user object, otherwise user_save() changes the password to the
    // hashed password.
    $account = entity_create('user', (array) $user);
    $account->save();

    // Store UID.
    $user->uid = $account->id();
  }

  /**
   * {@inheritDoc}
   */
  public function roleCreate(array $permissions) {
    // Generate a random, lowercase machine name.
    $rid = strtolower($this->random->name(8, TRUE));

    // Generate a random label.
    $name = trim($this->random->name(8, TRUE));

    // Check the all the permissions strings are valid.
    if (!$this->checkPermissions($permissions)) {
      throw new \RuntimeException('All permissions are not valid.');
    }

    // Create new role.
    $role = entity_create('user_role', array(
      'id' => $rid,
      'label' => $name,
    ));
    $result = $role->save();

    if ($result === SAVED_NEW) {
      // Grant the specified permissions to the role, if any.
      if (!empty($permissions)) {
        user_role_grant_permissions($role->id(), $permissions);

        // TODO: Fix this.
        /*$assigned_permissions = db_query('SELECT permission FROM {role_permission} WHERE rid = :rid', array(':rid' => $role->id()))->fetchCol();
        $missing_permissions = array_diff($permissions, $assigned_permissions);
        if ($missing_permissions) {
          return FALSE;
        }*/
      }
      return $role->id();
    }
    else {
      return FALSE;
    }
  }

  /**
   * {@inheritDoc}
   */
  public function roleDelete($rid) {
    $role = user_role_load($rid);

    if (!$role) {
      throw new \RuntimeException(sprintf('No role "%s" exists.', $rid));
    }

    $role->delete();
  }

  public function processBatch() {
    $this->validateDrupalSite();
    $batch =& batch_get();
    $batch['progressive'] = FALSE;
    batch_process();
  }

  /**
   * Check to make sure that the array of permissions are valid.
   *
   * @param array $permissions
   *   Permissions to check.
   * @param bool $reset
   *   Reset cached available permissions.
   * @return bool TRUE or FALSE depending on whether the permissions are valid.
   */
  protected function checkPermissions(array $permissions, $reset = FALSE) {
    $available = &drupal_static(__FUNCTION__);

    if (!isset($available) || $reset) {
      $available = array_keys(\Drupal::moduleHandler()->invokeAll('permission'));
    }

    $valid = TRUE;
    foreach ($permissions as $permission) {
      if (!in_array($permission, $available)) {
        $valid = FALSE;
      }
    }
    return $valid;
  }

  /**
   * {@inheritDoc}
   */
  public function userDelete(\stdClass $user) {
    user_cancel(array(), $user->uid, 'user_cancel_delete');
  }

  /**
   * {@inheritDoc}
   */
  public function userAddRole(\stdClass $user, $role_name) {
    // Allow both machine and human role names.
    $roles = user_role_names();
    if ($id = array_search($role_name, $roles)) {
      $role_name = $id;
    }
    $role = user_role_load($role_name);

    if (!$role) {
      throw new \RuntimeException(sprintf('No role "%s" exists.', $role_name));
    }

    $account = \user_load($user->uid);
    $account->addRole($role->id());
    $account->save();
  }

  /**
   * {@inheritDoc}
   */
  public function validateDrupalSite() {
    if ('default' !== $this->uri) {
      // Fake the necessary HTTP headers that Drupal needs:
      $drupal_base_url = parse_url($this->uri);
      // If there's no url scheme set, add http:// and re-parse the url
      // so the host and path values are set accurately.
      if (!array_key_exists('scheme', $drupal_base_url)) {
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
   * {@inheritDoc}
   */
  public function termCreate(\stdClass $term) {
    $term->vid = $term->vocabulary_machine_name;
    $entity = Term::create((array) $term);
    $entity->save();

    $term->tid = $entity->id();
    return $term;
  }

  /**
   * {@inheritDoc}
   */
  public function termDelete(\stdClass $term) {
    $term = Term::load($term->tid);
    $term->delete();
  }

}
