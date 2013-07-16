<?php

namespace Drupal\DrupalExtension\Hook\Annotation;

/**
 * BeforeTermCreate hook class.
 */
class BeforeTermCreate extends EntityHook {
  /**
   * {@inheritdoc}
   */
  public function getEventName() {
    return 'beforeTermCreate';
  }
}
