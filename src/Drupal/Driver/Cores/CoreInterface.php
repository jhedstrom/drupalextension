<?php

namespace Drupal\Driver\Cores;

/**
 * Drupal core interface.
 */
interface CoreInterface {
  /**
   * Instantiate the core interface.
   *
   * @param string $drupalRoot
   *
   * @param string $uri
   *   URI that is accessing Drupal. Defaults to 'default'.
   */
  public function __construct($drupalRoot, $uri = 'default');

  /**
   * Bootstrap Drupal.
   */
  public function bootstrap();

  /**
   * Clear caches.
   */
  public function clearCache();

  /**
   * Run cron.
   *
   * @return boolean
   *   True if cron runs, otherwise false.
   */
  public function runCron();

  /**
   * Create a node.
   */
  public function nodeCreate($node);

  /**
   * Delete a node.
   */
  public function nodeDelete($node);

  /**
   * Create a user.
   */
  public function userCreate(\stdClass $user);

  /**
   * Delete a user.
   */
  public function userDelete(\stdClass $user);

  /**
   * Add a role to a user.
   */
  public function userAddRole(\stdClass $user, $role_name);

  /**
   * Validate, and prepare environment for Drupal bootstrap.
   *
   * @throws BootstrapException
   *
   * @see _drush_bootstrap_drupal_site_validate()
   */
  public function validateDrupalSite();

  /**
   * Create a taxonomy term.
   */
  public function termCreate(\stdClass $term);

  /**
   * Delete a taxonomy term,
   */
  public function termDelete(\stdClass $term);

  /**
   * Create a role
   *
   * @param array $permissions
   *   An array of permissions to create the role with.
   *
   * @return string
   *   Role ID of newly created role, or FALSE if role creation failed.
   */
  public function roleCreate(array $permissions);

  /**
   * Delete a role
   *
   * @param $rid
   *   A role name to delete.
   */
  public function roleDelete($rid);

}
