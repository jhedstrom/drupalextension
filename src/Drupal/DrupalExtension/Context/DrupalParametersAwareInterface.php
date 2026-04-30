<?php

declare(strict_types=1);

namespace Drupal\DrupalExtension\Context;

use Behat\Behat\Context\Context;

/**
 * Interface for contexts that consume the Drupal extension parameters.
 *
 * Implemented by Drupal-aware Mink contexts (such as 'MessageContext') that
 * need access to the global 'selectors:' / 'text:' / 'region_map:' values
 * from 'behat.yml' but do not require the full Drupal driver wiring of
 * 'DrupalAwareInterface'.
 */
interface DrupalParametersAwareInterface extends Context {

  /**
   * Sets parameters provided for Drupal.
   *
   * @param array<string, mixed> $parameters
   *   The Drupal extension parameters.
   */
  public function setDrupalParameters(array $parameters): void;

}
