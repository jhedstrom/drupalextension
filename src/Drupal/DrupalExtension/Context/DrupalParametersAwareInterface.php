<?php

declare(strict_types=1);

namespace Drupal\DrupalExtension\Context;

/**
 * Declares Drupal extension parameters availability.
 */
interface DrupalParametersAwareInterface {

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
