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
 */
trait DeprecationTrait {

  /**
   * {@inheritdoc}
   */
  public function triggerDeprecation(string $message): void {
    static $emitted = [];

    if (isset($emitted[$message])) {
      return;
    }

    fwrite(STDERR, '[Deprecation] ' . $message . PHP_EOL);
    $emitted[$message] = TRUE;
  }

}
