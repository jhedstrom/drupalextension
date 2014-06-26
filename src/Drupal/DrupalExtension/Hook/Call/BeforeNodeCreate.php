<?php

namespace Drupal\DrupalExtension\Hook\Call;

/**
 * BeforeNodeCreate hook class.
 */
class BeforeNodeCreate extends EntityHook {
  /**
   * {@inheritdoc}
   */
  public function getName() {
    return 'beforeNodeCreate';
  }
}
