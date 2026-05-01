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
 * overrides the configured value in either direction. Recognised values
 * are '1'/'0', 'true'/'false', 'yes'/'no', 'on'/'off' (case-insensitive,
 * trimmed); unset or unparseable values fall back to the config value.
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
    $env = getenv('BEHAT_DRUPALEXTENSION_SUPPRESS_DEPRECATIONS');

    if ($env !== FALSE && $env !== '') {
      $normalized = strtolower(trim($env));

      if (in_array($normalized, ['1', 'true', 'yes', 'on'], TRUE)) {
        return TRUE;
      }

      if (in_array($normalized, ['0', 'false', 'no', 'off'], TRUE)) {
        return FALSE;
      }
    }

    return $this->getParameter('suppress_deprecations') === TRUE;
  }

}
