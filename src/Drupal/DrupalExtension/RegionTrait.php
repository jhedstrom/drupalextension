<?php

declare(strict_types=1);

namespace Drupal\DrupalExtension;

use Behat\Mink\Exception\ElementNotFoundException;

/**
 * Provides a method to find a region on the current page.
 *
 * Resolution depends only on Mink's Selectors registry and 'getSession()',
 * so this trait works on any 'RawMinkContext' consumer - no Drupal driver
 * bootstrap required. The 'region' selector is registered by the Drupal
 * extension service container at compile time (see
 * 'drupal.region_selector' in 'ServiceContainer/config/services.yml',
 * tagged 'mink.selector' with alias 'region'), so by the time any context
 * is instantiated the selector is already available in the registry.
 */
trait RegionTrait {

  /**
   * Return a region from the current page.
   *
   * @param string $region
   *   The machine name of the region to return.
   *
   * @return \Behat\Mink\Element\NodeElement
   *   The region element.
   *
   * @throws \Behat\Mink\Exception\ElementNotFoundException
   *   If region cannot be found.
   */
  public function getRegion(string $region) {
    $session = $this->getSession();
    $region_obj = $session->getPage()->find('region', $region);
    if (!$region_obj) {
      throw new ElementNotFoundException($session->getDriver(), 'region', 'name', $region);
    }

    return $region_obj;
  }

}
