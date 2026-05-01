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

  /**
   * Returns a specific Drupal parameter.
   *
   * @param string $name
   *   Parameter name.
   *
   * @return mixed
   *   The value, or NULL if the parameter is not set.
   */
  public function getDrupalParameter(string $name): mixed;

}
