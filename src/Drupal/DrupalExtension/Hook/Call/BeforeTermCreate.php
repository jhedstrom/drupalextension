<?php

namespace Drupal\DrupalExtension\Hook\Call;

/**
 * BeforeTermCreate hook class.
 */
class BeforeTermCreate extends EntityHook {
  /**
   * {@inheritdoc}
   */
  public function getName() {
    return 'beforeTermCreate';
  }
}
