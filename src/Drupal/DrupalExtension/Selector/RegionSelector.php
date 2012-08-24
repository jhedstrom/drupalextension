<?php

namespace Drupal\DrupalExtension\Selector;

use Behat\Mink\Selector\SelectorInterface;
use Behat\Mink\Selector\CssSelector;

/**
 * Custom "region" selector to help select Drupal regions
 */
class RegionSelector implements SelectorInterface {
  private $cssSelector;

  private $regionMap;

  public function __construct(CssSelector $cssSelector, array $regionMap) {
    $this->cssSelector = $cssSelector;
    $this->regionMap = $regionMap;
  }

  /**
   * Translates provided locator into XPath.
   *
   * @param string $region
   * @return string
   * @throws \InvalidArgumentException
   */
  public function translateToXPath($region) {
    if (!isset($this->regionMap[$region])) {
      throw new \InvalidArgumentException(sprintf('The "%s" region isn\'t configured!', $region));
    }
    $css = $this->regionMap[$region];

    return $this->cssSelector->translateToXPath($css);
  }
}
