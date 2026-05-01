<?php

declare(strict_types=1);

namespace Drupal\DrupalExtension;

/**
 * Provides helpful methods for dealing with Drupal extension parameters.
 *
 * These parameters are placed in behat.yml under 'Drupal\DrupalExtension'
 * and can be used to define commonly customized aspects of the Drupal
 * installation such as CSS selectors, interface text or region maps.
 *
 * This is the consumption point for parameter, text, and selector access
 * from any context, regardless of whether it inherits from
 * 'RawDrupalContext'. A context only needs to implement
 * 'ParametersAwareInterface' and 'use' this trait; 'DrupalAwareInitializer'
 * injects the parameter array via 'setParameters()' before any scenario
 * runs. No Drupal driver bootstrap is required.
 */
trait ParametersTrait {

  /**
   * Drupal extension parameters.
   *
   * @var array<string, mixed>
   */
  protected array $parameters = [];

  /**
   * Set parameters provided by the Drupal extension.
   *
   * @param array<string, mixed> $parameters
   *   The parameters to set.
   */
  public function setParameters(array $parameters): void {
    $this->parameters = $parameters;
  }

  /**
   * Returns a specific Drupal extension parameter.
   *
   * @param string $name
   *   Parameter name.
   *
   * @return mixed
   *   The value, or null if the parameter does not exist.
   */
  public function getParameter(string $name): mixed {
    return $this->parameters[$name] ?? NULL;
  }

  /**
   * Returns a specific Drupal text value.
   *
   * @param string $name
   *   Text value name, such as 'log_out', which corresponds to the default
   *   'Log out' link text.
   *
   * @return string
   *   The text value.
   *
   * @throws \RuntimeException
   *   Thrown when the text is not present in the list of parameters.
   */
  public function getDrupalText(string $name) {
    $text = $this->getParameter('text');
    if (!isset($text[$name])) {
      throw new \RuntimeException(sprintf('No such Drupal string: %s', $name));
    }
    return $text[$name];
  }

  /**
   * Returns a specific CSS selector.
   *
   * @param string $name
   *   The name of the CSS selector.
   *
   * @return string
   *   The CSS selector.
   *
   * @throws \RuntimeException
   *   Thrown when the selector is not present in the list of parameters.
   */
  public function getDrupalSelector(string $name) {
    $text = $this->getParameter('selectors');
    if (!isset($text[$name])) {
      throw new \RuntimeException(sprintf('No such selector configured: %s', $name));
    }
    return $text[$name];
  }

}
