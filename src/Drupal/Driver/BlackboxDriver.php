<?php

namespace Drupal\Driver;

/**
 * Implements DriverInterface.
 */
class BlackboxDriver extends BaseDriver {

  /**
   * Implements DriverInterface::isBootstrapped().
   */
  public function isBootstrapped() {
    // Assume the blackbox is always bootstrapped.
    return TRUE;
  }

}
