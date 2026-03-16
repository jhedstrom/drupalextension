<?php

declare(strict_types=1);

namespace Drupal\DrupalExtension\Hook\Attribute;

/**
 * Attribute for methods to run after a node is created.
 */
#[\Attribute(\Attribute::TARGET_METHOD | \Attribute::IS_REPEATABLE)]
final class AfterNodeCreate implements DrupalHook {

  public function __construct(public ?string $filterString = NULL) {
  }

  /**
   * Returns the filter string for this hook.
   */
  public function getFilterString(): ?string {
    return $this->filterString;
  }

}
