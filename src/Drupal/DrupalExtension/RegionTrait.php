<?php

declare(strict_types=1);

namespace Drupal\DrupalExtension;

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
   * @throws \Exception
   *   If region cannot be found.
   */
  public function getRegion(string $region) {
    $session = $this->getSession();
    $regionObj = $session->getPage()->find('region', $region);
    if (!$regionObj) {
      throw new \Exception(sprintf('No region "%s" found on the page %s.', $region, $session->getCurrentUrl()));
    }

    return $regionObj;
  }

}
