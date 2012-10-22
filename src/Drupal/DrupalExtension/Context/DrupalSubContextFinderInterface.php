<?php

namespace Drupal\DrupalExtension\Context;

/**
 * Interface for discovery of subcontexts.
 */
interface DrupalSubContextFinderInterface {

  /**
   * Returns an array of paths in which to look for Drupal subcontexts.
   *
   * @return array
   *   An array of paths in which to find subcontexts.
   */
  public function getSubContextPaths();
}
