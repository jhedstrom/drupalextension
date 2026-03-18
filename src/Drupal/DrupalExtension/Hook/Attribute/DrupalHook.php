<?php

declare(strict_types=1);

namespace Drupal\DrupalExtension\Hook\Attribute;

/**
 * Marker interface for Drupal Extension hook attributes.
 */
// phpcs:ignore Drupal.Classes.InterfaceName.InterfaceSuffix
interface DrupalHook {

  /**
   * Returns the filter string for this hook.
   */
  public function getFilterString(): ?string;

}
