<?php

declare(strict_types=1);

namespace Drupal\DrupalExtension\Context\Environment\Reader;

use Behat\Behat\Context\Environment\ContextEnvironment;
use Behat\Testwork\Environment\Environment;
use Behat\Testwork\Environment\Exception\EnvironmentReadException;
use Behat\Testwork\Environment\Reader\EnvironmentReader;

/**
 * Drupal-aware environment reader.
 *
 * Sub-context auto-discovery was removed in 6.0.0. The reader is retained as
 * a service-container hook for any future Drupal-specific context loading.
 */
final class Reader implements EnvironmentReader {

  /**
   * {@inheritdoc}
   */
  public function supportsEnvironment(Environment $environment) {
    return $environment instanceof ContextEnvironment;
  }

  /**
   * {@inheritdoc}
   */
  public function readEnvironmentCallees(Environment $environment) {
    if (!$environment instanceof ContextEnvironment) {
      throw new EnvironmentReadException(sprintf(
        'ContextEnvironmentReader does not support `%s` environment.',
        $environment::class
      ), $environment);
    }

    return [];
  }

}
