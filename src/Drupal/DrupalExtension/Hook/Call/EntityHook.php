<?php

namespace Drupal\DrupalExtension\Hook\Call;

use Behat\Testwork\Hook\Call\RuntimeFilterableHook;
use Behat\Testwork\Hook\Scope\HookScope;

/**
 * Entity hook class.
 */
abstract class EntityHook extends RuntimeFilterableHook {

  /**
   * {@inheritDoc}
   */
  public function filterMatches(HookScope $scope) {
    if (NULL === ($filterString = $this->getFilterString())) {
      return TRUE;
    }
  }

}
