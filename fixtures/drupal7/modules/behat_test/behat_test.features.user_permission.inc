<?php
/**
 * @file
 * behat_test.features.user_permission.inc
 */

/**
 * Implements hook_user_default_permissions().
 */
function behat_test_user_default_permissions() {
  $permissions = array();

  // Exported permission: 'create post content'.
  $permissions['create post content'] = array(
    'name' => 'create post content',
    'roles' => array(),
    'module' => 'node',
  );

  // Exported permission: 'delete any post content'.
  $permissions['delete any post content'] = array(
    'name' => 'delete any post content',
    'roles' => array(),
    'module' => 'node',
  );

  // Exported permission: 'delete own post content'.
  $permissions['delete own post content'] = array(
    'name' => 'delete own post content',
    'roles' => array(),
    'module' => 'node',
  );

  // Exported permission: 'edit any post content'.
  $permissions['edit any post content'] = array(
    'name' => 'edit any post content',
    'roles' => array(),
    'module' => 'node',
  );

  // Exported permission: 'edit own post content'.
  $permissions['edit own post content'] = array(
    'name' => 'edit own post content',
    'roles' => array(),
    'module' => 'node',
  );

  return $permissions;
}
