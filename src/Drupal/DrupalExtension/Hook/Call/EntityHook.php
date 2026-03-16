<?php

declare(strict_types=1);

namespace Drupal\DrupalExtension\Hook\Call;

use Behat\Testwork\Hook\Call\RuntimeFilterableHook;
use Behat\Testwork\Hook\Scope\HookScope;

/**
 * Entity hook class.
 */
abstract class EntityHook extends RuntimeFilterableHook {

  /**
   * {@inheritdoc}
   */
  public function filterMatches(HookScope $scope) {
    return $this->getFilterString() === NULL;
  }

}
