<?php

namespace Drupal\DrupalExtension\Hook\Annotation;

/**
 * BeforeUserCreate hook class.
 */
class BeforeUserCreate extends EntityHook {
  /**
   * {@inheritdoc}
   */
  public function getEventName() {
    return 'beforeUserCreate';
  }
}
