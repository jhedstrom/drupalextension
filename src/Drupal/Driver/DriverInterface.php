<?php

namespace Drupal\Driver;

/**
 * Driver interface.
 */
interface DriverInterface {

  /**
   * Bootstrap operations, as needed.
   */
  public function bootstrap();

  /**
   * Determine if the driver has been bootstrapped.
   */
  public function isBootstrapped();

  /**
   * Create a user.
   */
  public function userCreate(\stdClass $user);

  /**
   * Delete a user.
   */
  public function userDelete(\stdClass $user);

  public function processBatch();

  /**
   * Add a role for a user.
   *
   * @param stdClass $user
   *   A user object.
   * @param string $role
   *   The role name to assign.
   */
  public function userAddRole(\stdClass $user, $role);

  /**
   * Retrieve watchdog entries.
   *
   * @param integer $count
   *   Number of entries to retrieve.
   * @param string $type
   *   Filter by watchdog type.
   * @param string $severity
   *   Filter by watchdog severity level.
   *
   * @return string
   *   Watchdog output.
   */
  public function fetchWatchdog($count = 10, $type = NULL, $severity = NULL);

  /**
   * Clear Drupal caches.
   *
   * @param string $type
   *   Type of cache to clear defaults to all.
   */
  public function clearCache($type = NULL);

  /**
   * Create a node.
   *
   * @param object $node
   *   Fully loaded node object.
   * @return object
   *   The node object including the node ID in the case of new nodes.
   */
  public function createNode(\stdClass $node);

  /**
   * Delete a node.
   *
   * @param object $node
   *   Fully loaded node object.
   */
  public function nodeDelete(\stdClass $node);

  /**
   * Run cron.
   */
  public function runCron();

  /**
   * Create a taxonomy term.
   *
   * @param object $term
   *   Term object.
   * @return object
   *   The term object including the term ID in the case of new terms.
   */
  public function createTerm(\stdClass $term);
}
