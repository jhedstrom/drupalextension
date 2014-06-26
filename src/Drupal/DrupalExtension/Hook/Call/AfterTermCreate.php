<?php

namespace Drupal\DrupalExtension\Hook\Call;

/**
 * AfterTermCreate hook class.
 */
class AfterTermCreate extends EntityHook {
  /**
   * {@inheritdoc}
   */
  public function getName() {
    return 'afterTermCreate';
  }
}
