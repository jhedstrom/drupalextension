<?php

namespace Drupal;

use Drupal\Driver\DriverInterface;

/**
 * Drupal driver manager.
 */
class Drupal {
  /**
   * Default driver.
   */
  private $defaultDriverName;

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

  /**
   * Return a registered driver by name, or the default driver.
   *
   * @throws \InvalidArgumentException
   */
  public function getDriver($name = NULL) {
    $name = strtolower($name) ?: $this->defaultDriverName;

    if (NULL === $name) {
      throw new \InvalidArgumentException('Specify a Drupal driver to get.');
    }

    if (!isset($this->drivers[$name])) {
      throw new \InvalidArgumentException(sprintf('Driver "%s" is not registered', $name));
    }

    return $this->drivers[$name];
  }

  /**
   * Set the default driver name.
   *
   * @param string $name
   *   Default session name to set.
   *
   * @throws \InvalidArgumentException
   */
  public function setDefaultDriverName($name) {
    $name = strtolower($name);

    if (!isset($this->drivers[$name])) {
      throw new \InvalidArgumentException(sprintf('Driver "%s" is not registered.', $name));
    }

    $this->defaultDriverName = $name;
  }
}
