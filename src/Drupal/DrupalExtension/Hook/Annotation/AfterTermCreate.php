<?php

namespace Drupal\DrupalExtension\Hook\Annotation;

/**
 * AfterTermCreate hook class.
 */
class AfterTermCreate extends EntityHook {
  /**
   * {@inheritdoc}
   */
  public function getEventName() {
    return 'afterTermCreate';
  }
}
