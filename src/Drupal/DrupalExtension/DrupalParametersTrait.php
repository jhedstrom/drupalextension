<?php

declare(strict_types=1);

namespace Drupal\DrupalExtension;

/**
 * Provides helpful methods for dealing with Drupal parameters.
 *
 * These parameters are placed in behat.yml and can be used to define commonly
 * customized aspects of the Drupal installation such as CSS selectors,
 * interface text or region maps.
 */
trait DrupalParametersTrait {

  /**
   * Test parameters.
   *
   * @var array
   */
  protected $drupalParameters;

  /**
   * Set parameters provided for Drupal.
   *
   * @param array $parameters
   *   The parameters to set.
   */
  public function setDrupalParameters(array $parameters): void {
    $this->drupalParameters = $parameters;
  }

  /**
   * Returns a specific Drupal parameter.
   *
   * @param string $name
   *   Parameter name.
   *
   * @return mixed
   *   The value, or null if the parameter does not exist.
   */
  public function getDrupalParameter(string $name): mixed {
    return $this->drupalParameters[$name] ?? NULL;
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
   * @throws \Exception
   *   Thrown when the text is not present in the list of parameters.
   */
  public function getDrupalText(string $name) {
    $text = $this->getDrupalParameter('text');
    if (!isset($text[$name])) {
      throw new \Exception(sprintf('No such Drupal string: %s', $name));
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
   * @throws \Exception
   *   Thrown when the selector is not present in the list of parameters.
   */
  public function getDrupalSelector(string $name) {
    $text = $this->getDrupalParameter('selectors');
    if (!isset($text[$name])) {
      throw new \Exception(sprintf('No such selector configured: %s', $name));
    }
    return $text[$name];
  }

}
