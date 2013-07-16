<?php

namespace Drupal\DrupalExtension\Hook\Annotation;

/**
 * BeforeNodeCreate hook class.
 */
class BeforeNodeCreate extends EntityHook {
  /**
   * {@inheritdoc}
   */
  public function getEventName() {
    return 'beforeNodeCreate';
  }
}
