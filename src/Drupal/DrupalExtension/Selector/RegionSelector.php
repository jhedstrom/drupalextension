<?php

declare(strict_types=1);

namespace Drupal\DrupalExtension\Selector;

use Behat\Mink\Selector\SelectorInterface;
use Behat\Mink\Selector\CssSelector;

/**
 * Custom "region" selector to help select Drupal regions.
 */
class RegionSelector implements SelectorInterface {

  /**
   * Constructs a RegionSelector.
   *
   * @param \Behat\Mink\Selector\CssSelector $cssSelector
   *   The CSS selector.
   * @param array<string, string> $regionMap
   *   Map of region names to CSS selectors.
   */
  public function __construct(private readonly CssSelector $cssSelector, private array $regionMap) {
  }

  /**
   * Translates provided locator into XPath.
   *
   * @param string $region
   *   The region name to translate.
   *
   * @return string
   *   The XPath for the region.
   *
   * @throws \InvalidArgumentException
   */
  // phpcs:ignore Drupal.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
  public function translateToXPath($region) {
    if (!isset($this->regionMap[$region])) {
      throw new \InvalidArgumentException(sprintf('The "%s" region isn\'t configured!', $region));
    }
    $css = $this->regionMap[$region];

    return $this->cssSelector->translateToXPath($css);
  }

}
