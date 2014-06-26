<?php

namespace Drupal\DrupalExtension\Hook\Call;

/**
 * AfterUserCreate hook class.
 */
class AfterUserCreate extends EntityHook {
  /**
   * {@inheritdoc}
   */
  public function getName() {
    return 'afterUserCreate';
  }
}
