<?php

declare(strict_types=1);

namespace Drupal\MinkExtension\Context;

use Behat\Behat\Context\Context;

/**
 * Interface for contexts that consume the Mink extension parameters.
 *
 * Mirrors the symmetry of 'DrupalParametersAwareInterface' (set + get) for
 * the Mink side. Upstream 'Behat\MinkExtension\Context\MinkAwareContext'
 * declares 'setMinkParameters()' but no corresponding getter on the
 * interface itself - the 'getMinkParameter()' helper lives only on
 * 'RawMinkContext'. This local interface exposes both halves of the
 * contract so contexts that depend on Mink parameters (such as
 * 'MessageContext') can be type-checked against the consumer contract
 * without inheriting the full 'RawMinkContext' implementation.
 */
interface MinkParametersAwareInterface extends Context {

  /**
   * Sets parameters provided for Mink.
   *
   * Method signature intentionally matches upstream
   * 'Behat\MinkExtension\Context\MinkAwareContext::setMinkParameters()'
   * (no return type, untyped to remain compatible with classes that
   * already inherit it from 'RawMinkContext').
   *
   * @param array<string, mixed> $parameters
   *   The Mink extension parameters.
   *
   * @phpstan-ignore missingType.return
   */
  public function setMinkParameters(array $parameters);

  /**
   * Returns a specific Mink parameter.
   *
   * Method signature intentionally matches upstream
   * 'Behat\MinkExtension\Context\RawMinkContext::getMinkParameter()'
   * (untyped parameter and return for compatibility with classes that
   * inherit it).
   *
   * @param string $name
   *   Parameter name.
   *
   * @return mixed
   *   The value, or NULL if the parameter is not set.
   */
  public function getMinkParameter($name);

}
