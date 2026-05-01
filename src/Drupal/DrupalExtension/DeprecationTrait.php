<?php

declare(strict_types=1);

namespace Drupal\DrupalExtension;

/**
 * Default 'DeprecationInterface' implementation.
 *
 * Writes the deprecation notice straight to 'STDERR'. Behat installs an
 * error handler that escalates 'E_USER_DEPRECATED' to a step failure, so
 * 'trigger_error()' is the wrong vehicle here. The 'STDERR' write keeps
 * the notice visible to test authors while leaving the step state intact.
 * Each unique message is emitted at most once per process.
 *
 * Includes 'ParametersTrait' to read the 'suppress_deprecations' value via
 * the standard 'getParameter()' accessor. The
 * 'BEHAT_DRUPALEXTENSION_SUPPRESS_DEPRECATIONS' environment variable
 * overrides the configured value in either direction. See
 * 'DeprecationSuppression' for the resolution rules.
 */
trait DeprecationTrait {

  use ParametersTrait;

  /**
   * {@inheritdoc}
   */
  public function triggerDeprecation(string $message): void {
    static $emitted = [];

    if ($this->isDeprecationSuppressed()) {
      return;
    }

    if (isset($emitted[$message])) {
      return;
    }

    fwrite(STDERR, '[Deprecation] ' . $message . PHP_EOL);
    $emitted[$message] = TRUE;
  }

  /**
   * Determines whether deprecation emission is currently suppressed.
   */
  protected function isDeprecationSuppressed(): bool {
    $config_value = $this->getParameter('suppress_deprecations');

    return DeprecationSuppression::shouldSuppress(is_bool($config_value) ? $config_value : NULL);
  }

}
