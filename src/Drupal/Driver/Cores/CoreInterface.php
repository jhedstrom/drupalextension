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
   */
  public function __construct($drupalRoot);

  /**
   * Bootstrap Drupal.
   */
  public function bootstrap();

  /**
   * Validate, and prepare environment for Drupal bootstrap.
   *
   * @throws BootstrapException
   *
   * @see _drush_bootstrap_drupal_site_validate()
   */
  public function validateDrupalSite();
}
