<?php

namespace Drupal\DrupalExtension\Hook\Annotation;

/**
 * AfterUserCreate hook class.
 */
class AfterUserCreate extends EntityHook {
  /**
   * {@inheritdoc}
   */
  public function getEventName() {
    return 'afterUserCreate';
  }
}
