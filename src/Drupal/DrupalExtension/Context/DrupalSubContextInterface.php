<?php

namespace Drupal\DrupalExtension\Context;

interface DrupalSubContextInterface {
  /**
   * Return a unique alias for this sub-context.
   *
   * @return string
   */
  public static function getAlias();
}
