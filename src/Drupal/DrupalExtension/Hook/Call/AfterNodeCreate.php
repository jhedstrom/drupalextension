<?php

namespace Drupal\DrupalExtension\Hook\Call;

/**
 * AfterNodeCreate hook class.
 */
class AfterNodeCreate extends EntityHook {
  /**
   * {@inheritdoc}
   */
  public function getName() {
    return 'afterNodeCreate';
  }
}
