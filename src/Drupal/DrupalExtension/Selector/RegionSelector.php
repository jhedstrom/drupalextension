<?php

declare(strict_types=1);

namespace Drupal\DrupalExtension\Selector;

use Behat\Mink\Selector\SelectorInterface;
use Behat\Mink\Selector\CssSelector;

/**
 * Custom Mink selector that resolves human-readable region names to XPath.
 *
 * Mink supports user-defined selectors through its 'SelectorsHandler'
 * registry. The Drupal extension wires this class into the registry at
 * compile time via a service tagged 'mink.selector' with alias 'region'
 * (see 'drupal.region_selector' in
 * 'src/Drupal/DrupalExtension/ServiceContainer/config/services.yml').
 *
 * Once registered, any context with a Mink session can call
 * '$page->find("region", "Header")' and Mink dispatches to
 * 'translateToXPath()' here. The class looks the name up in the user's
 * configured map (the 'regions' / legacy 'region_map' key under
 * 'Drupal\DrupalExtension' in 'behat.yml') and delegates the
 * CSS-to-XPath translation to the wrapped 'CssSelector'.
 *
 * Regions are a generic page concept - this selector has no Drupal API
 * dependency and works against any HTML page.
 */
class RegionSelector implements SelectorInterface {

  /**
   * Constructs a RegionSelector.
   *
   * @param \Behat\Mink\Selector\CssSelector $cssSelector
   *   The CSS selector that performs the actual CSS-to-XPath translation.
   * @param array<string, string> $regions
   *   Map of region names to CSS selectors, sourced from the 'regions' key
   *   (or the deprecated 'region_map') under 'Drupal\DrupalExtension'.
   */
  public function __construct(private readonly CssSelector $cssSelector, private array $regions) {
  }

  /**
   * Translates a region name into XPath.
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
    if (!isset($this->regions[$region])) {
      throw new \InvalidArgumentException(sprintf('The "%s" region isn\'t configured!', $region));
    }
    $css = $this->regions[$region];

    return $this->cssSelector->translateToXPath($css);
  }

}
