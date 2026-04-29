<?php

declare(strict_types=1);

namespace Drupal\DrupalExtension;

use Behat\Mink\Exception\ElementNotFoundException;

/**
 * Provides a method to find a region on the current page.
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
