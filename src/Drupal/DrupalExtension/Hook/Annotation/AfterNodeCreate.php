<?php

namespace Drupal\DrupalExtension\Hook\Annotation;

/**
 * AfterNodeCreate hook class.
 */
class AfterNodeCreate extends EntityHook {
  /**
   * {@inheritdoc}
   */
  public function getEventName() {
    return 'afterNodeCreate';
  }
}
