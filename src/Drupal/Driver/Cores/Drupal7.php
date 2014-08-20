<?php

namespace Drupal\Driver\Cores;

use Drupal\Component\Utility\Random;
use Drupal\Exception\BootstrapException;

/**
 * Drupal 7 core.
 */
class Drupal7 implements CoreInterface {
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
      require_once DRUPAL_ROOT . '/includes/bootstrap.inc';
      $this->validateDrupalSite();
    }

    // Bootstrap Drupal.
    $current_path = getcwd();
    chdir(DRUPAL_ROOT);
    drupal_bootstrap(DRUPAL_BOOTSTRAP_CONFIGURATION);
    if (empty($GLOBALS['databases'])) {
      throw new BootstrapException('Missing database setting, verify the database configuration in settings.php.');
    }
    drupal_bootstrap(DRUPAL_BOOTSTRAP_FULL);
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
    // Set original if not set.
    if (!isset($node->original)) {
      $node->original = clone $node;
    }

    // Assign authorship if none exists and `author` is passed.
    if (!isset($node->uid) && !empty($node->author) && ($user = user_load_by_name($node->author))) {
      $node->uid = $user->uid;
    }

    // Convert properties to expected structure.
    $this->expandEntityProperties($node);

    // Attempt to decipher any fields that may be specified.
    $node = $this->expandEntityFields($node);

    // Set defaults that haven't already been set.
    $defaults = clone $node;
    node_object_prepare($defaults);
    $node = (object) array_merge((array) $defaults, (array) $node);

    node_save($node);
    return $node;
  }

  /**
   * {@inheritDoc}
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
   * {@inheritDoc}
   */
  public function userCreate(\stdClass $user) {
    // Default status to TRUE if not explicitly creating a blocked user.
    if (!isset($user->status)) {
      $user->status = 1;
    }

    // Convert roles to proper structure.
    if (isset($user->roles)) {
      foreach ($user->roles as $key => $rid) {
        $role = user_role_load($rid);
        unset($user->roles[$key]);
        $user->roles[$rid] = $role->name;

      }
    }

    // Clone user object, otherwise user_save() changes the password to the
    // hashed password.
    $account = clone $user;

    user_save($account, (array) $user);

    // Store UID.
    $user->uid = $account->uid;
  }

  /**
   * {@inheritDoc}
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
   * {@inheritDoc}
   */
  public function userAddRole(\stdClass $user, $role_name) {
    $role = user_role_load_by_name($role_name);

    if (!$role) {
      throw new \RuntimeException(sprintf('No role "%s" exists.', $role_name));
    }

    user_multiple_role_edit(array($user->uid), 'add_role', $role->rid);
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
      $available = array_keys(module_invoke_all('permission'));
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
  public function roleCreate(array $permissions) {

    // Both machine name and permission title are allowed.
    $all_permissions = $this->getAllPermissions();

    foreach ($permissions as $key => $name) {
      if (!isset($all_permissions[$name])) {
        $search = array_search($name, $all_permissions);
        if (!$search) {
          throw new \RuntimeException(sprintf("No permission '%s' exists.", $name));
        }
        $permissions[$key] = $search;
      }
    }

    // Create new role.
    $role = new \stdClass();
    $role->name = $this->random->name(8);
    user_role_save($role);
    user_role_grant_permissions($role->rid, $permissions);

    if ($role && !empty($role->rid)) {
      $count = db_query('SELECT COUNT(*) FROM {role_permission} WHERE rid = :rid', array(':rid' => $role->rid))->fetchField();
      if ($count == count($permissions)) {
        return $role->rid;
      }
      else {
        return FALSE;
      }

    }
    else {
      return FALSE;
    }
  }

  /**
   * {@inheritDoc}
   */
  public function roleDelete($rid) {
    user_role_delete((int) $rid);
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
   * Given a node object, expand fields to match the format expected by node_save().
   *
   * @param stdClass $entity
   *   Entity object.
   * @param string $entityType
   *   Entity type, defaults to node.
   * @param string $bundle
   *   Entity bundle.
   */
  protected function expandEntityFields(\stdClass $entity, $entityType = 'node', $bundle = '') {
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
                  $new_entity->{$param}[LANGUAGE_NONE][0][$column] = $value;
                }
                // Special handling for term references.
                elseif ('taxonomy' === $info['module']) {
                  $terms = explode(',', $value);
                  $i = 0;
                  foreach ($terms as $term) {
                    $tid = taxonomy_get_term_by_name($term);
                    if (!$tid) {
                      throw new \Exception(sprintf("No term '%s' exists.", $term));
                    }

                    $new_entity->{$param}[LANGUAGE_NONE][$i][$column] = array_shift($tid)->tid;
                    $i++;
                  }
                }

                elseif (is_array($value)) {
                  foreach ($value as $key => $data) {
                    if (is_int($key) && (isset($value[$key+1]) || isset($value[$key-1]))) {
                      $new_entity->{$param}[LANGUAGE_NONE][$key] = $data;
                    } else {
                      $new_entity->{$param}[LANGUAGE_NONE][0][$key] = $data;
                    }
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

  /**
   * Given an entity object, expand any property fields to the expected structure.
   */
  protected function expandEntityProperties(\stdClass $entity) {
    // The created field may come in as a readable date, rather than a timestamp.
    if (isset($entity->created) && !is_numeric($entity->created)) {
      $entity->created = strtotime($entity->created);
    }

    // Map human-readable node types to machine node types.
    $types = \node_type_get_types();
    foreach ($types as $type) {
      if ($entity->type == $type->name) {
        $entity->type = $type->type;
        continue;
      }
    }
  }

  /**
   * {@inheritDoc}
   */
  public function termCreate(\stdClass $term) {
    // Map vocabulary names to vid, these take precedence over machine names.
    if (!isset($term->vid)) {
      $vocabularies = \taxonomy_get_vocabularies();
      foreach ($vocabularies as $vid => $vocabulary) {
        if ($vocabulary->name == $term->vocabulary_machine_name) {
          $term->vid = $vocabulary->vid;
        }
      }
    }

    if (!isset($term->vid)) {

      // Try to load vocabulary by machine name.
      $vocabularies = \taxonomy_vocabulary_load_multiple(FALSE, array(
        'machine_name' => $term->vocabulary_machine_name
      ));
      if (!empty($vocabularies)) {
        $vids = array_keys($vocabularies);
        $term->vid = reset($vids);
      }
    }

    // If `parent` is set, look up a term in this vocab with that name.
    if (isset($term->parent)) {
      $parent = \taxonomy_get_term_by_name($term->parent, $term->vocabulary_machine_name);
      if (!empty($parent)) {
        $parent = reset($parent);
        $term->parent = $parent->tid;
      }
    }

    if (empty($term->vid)) {
      throw new \Exception(sprintf('No "%s" vocabulary found.'));
    }

    \taxonomy_term_save($term);

    // Loading a term by name returns an array of term objects, but there should
    // only be one matching term in a testing context, so take the first match
    // by reset()'ing $matches.
    $matches = \taxonomy_get_term_by_name($term->name);
    $saved_term = reset($matches);

    return $saved_term;
  }

  /**
   * {@inheritDoc}
   */
  public function termDelete(\stdClass $term) {
    $status = 0;
    if (isset($term->tid)) {
      $status = \taxonomy_term_delete($term->tid);
    }
    // Will be SAVED_DELETED (3) on success.
    return $status;
  }

  /**
   * Helper function to get all permissions.
   *
   * @return array
   *   Array keyed by permission name, with the human-readable title as the value.
   */
  protected function getAllPermissions() {
    $permissions = array();
    foreach (module_invoke_all('permission') as $name => $permission) {
      $permissions[$name] = $permission['title'];
    }
    return $permissions;
  }

}
