<?php

namespace Drupal\DrupalExtension\Hook\Call;

/**
 * BeforeUserCreate hook class.
 */
class BeforeUserCreate extends EntityHook {
  /**
   * {@inheritdoc}
   */
  public function getName() {
    return 'beforeUserCreate';
  }
}
