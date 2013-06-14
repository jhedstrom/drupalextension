<?php

namespace Drupal\Driver\Cores;

/**
 * Drupal 7 core.
 */
class Drupal7 implements CoreInterface {
  private $drupalRoot;
  private $uri;

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
  public function nodeCreate(\stdClass $node) {
    // Default status to 1 if not set.
    if (!isset($node->status)) {
      $node->status = 1;
    }

    // Set original if not set.
    if (!isset($node->original)) {
      $node->original = clone $node;
    }

    // Assign authorship if none exists and `author` is passed.
    if (!isset($node->uid) && !empty($node->author) && ($user = user_load_by_name($node->author))) {
      $node->uid = $user->uid;
    }

    // Attempt to decipher any fields that may be specified.
    $node = $this->expandEntityFields($node);

    node_save($node);
    return $node;
  }

  /**
   * Implements CoreInterface::nodeDelete().
   */
  public function nodeDelete(\stdClass $node) {
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

    user_save($account, (array) $user);

    // Store UID.
    $user->uid = $account->uid;
  }

  /**
   * Implements CoreInterface::userDelete().
   */
  public function userDelete(\stdClass $user) {
    user_cancel(array(), $user->uid, 'user_cancel_delete');
  }

  public function processBatch() {
    $batch =& batch_get();
    $batch['progressive'] = FALSE;
     batch_process();
  }

  /**
   * Implements CoreInterface::userAddRole().
   */
  public function userAddRole(\stdClass $user, $role_name) {
    $role = user_role_load_by_name($role_name);

    if (!$role) {
      throw new \RuntimeException(sprintf('No role "%s" exists.', $role_name));
    }

    user_multiple_role_edit(array($user->uid), 'add_role', $role->rid);
  }

  /**
   * Impelements CoreInterface::validateDrupalSite().
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
  }

  /**
   * Given a node object, expand fields to match the format expected by node_save().
   *
   * @param stdClass $entity
   *   Entity object.
   * @param string $entityType
   *   Entity type, defaults to node.
   * @param string $bundle
   *   Entity bundle.
   */
  function expandEntityFields(\stdClass $entity, $entityType = 'node', $bundle = '') {
    if ($entityType === 'node' && !$bundle) {
      $bundle = $entity->type;
    }

    $new_entity = clone $entity;
    foreach ($entity as $param => $value) {
      if ($info = field_info_field($param)) {
        foreach ($info['bundles'] as $type => $bundles) {
          if ($type == $entityType) {
            foreach ($bundles as $target_bundle) {
              if ($bundle === $target_bundle) {
                unset($new_entity->{$param});

                // Use the first defined column. @todo probably breaks things.
                $column_names = array_keys($info['columns']);
                $column = array_shift($column_names);

                // Special handling for date fields (start/end).
                // @todo generalize this
                if ('date' === $info['module']) {
                  // Dates passed in separated by a comma are start/end dates.
                  $dates = explode(',', $value);
                  $value = trim($dates[0]);
                  if (!empty($dates[1])) {
                    $column2 = array_shift($column_names);
                    $new_entity->{$param}[LANGUAGE_NONE][0][$column2] = trim($dates[1]);
                  }
                }
                // Special handling for term references.
                elseif ('taxonomy' === $info['module']) {
                  $terms = explode(',', $value);
                  $i = 0;
                  foreach ($terms as $term) {
                    $term = taxonomy_get_term_by_name($term);
                    $new_entity->{$param}[LANGUAGE_NONE][$i][$column] = array_shift($term)->tid;
                    $i++;
                  }
                }
                else {
                  $new_entity->{$param}[LANGUAGE_NONE][0][$column] = $value;
                }
              }
            }
          }
        }
      }
    }

    return $new_entity;
  }
}
