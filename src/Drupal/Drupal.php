<?php

namespace Drupal;

use Drupal\Driver\DriverInterface;

/**
 * Drupal driver manager.
 */
class Drupal {
  /**
   * All initiated drivers.
   */
  private $drivers = array();

  /**
   * Initialize the driver manager.
   */
  public function __construct(array $drivers = array()) {
    foreach ($drivers as $name => $driver) {
      $this->registerDriver($name, $driver);
    }
  }

  /**
   * Register a new driver.
   *
   * @param string $name
   *   Driver name.
   * @param DrupalDriver $driver
   *   An instance of a DriverInterface.
   */
  public function registerDriver($name, DriverInterface $driver) {
    $name = strtolower($name);
    $this->drivers[$name] = $driver;
  }
}
