<?php

declare(strict_types=1);

namespace Drupal\DrupalExtension;

use Behat\Mink\Exception\ElementNotFoundException;

/**
 * Provides a method to find a named region on the current page.
 *
 * Regions are a generic page concept, not Drupal-specific. Resolution
 * goes through Mink's Selectors registry via 'getSession()', so this
 * trait works on any 'RawMinkContext' consumer with no Drupal API
 * bootstrap. The 'region' selector itself is registered at service
 * container compile time (see 'drupal.region_selector' in
 * 'ServiceContainer/config/services.yml', tagged 'mink.selector' with
 * alias 'region') and reads the user's region map from 'behat.yml'.
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
