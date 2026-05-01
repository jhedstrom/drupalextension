<?php

declare(strict_types=1);

namespace Drupal\DrupalExtension\Context;

/**
 * Declares Drupal extension parameters availability.
 */
interface ParametersAwareInterface {

  /**
   * Sets parameters provided by the Drupal extension.
   *
   * @param array<string, mixed> $parameters
   *   The Drupal extension parameters.
   */
  public function setParameters(array $parameters): void;

  /**
   * Returns a specific Drupal extension parameter.
   *
   * @param string $name
   *   Parameter name.
   *
   * @return mixed
   *   The value, or NULL if the parameter is not set.
   */
  public function getParameter(string $name): mixed;

}
