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
   * Validate, and prepare environment for Drupal bootstrap.
   *
   * @throws BootstrapException
   *
   * @see _drush_bootstrap_drupal_site_validate()
   */
  public function validateDrupalSite();
}
