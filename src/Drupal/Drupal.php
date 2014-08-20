<?php

namespace Drupal;

use Behat\Testwork\Environment\Environment;

use Drupal\Driver\DriverInterface;
use Drupal\Component\Utility\Random;

/**
 * Drupal driver manager.
 */
class Drupal {
  /**
   * Default driver.
   *
   * @var string
   */
  private $defaultDriverName;

  /**
   * All initiated drivers.
   *
   * @var array
   */
  private $drivers = array();

  /**
   * Behat environment.
   *
   * @var \Behat\Testwork\Environment\Environment
   */
  private $environment;

  /**
   * Random generator.
   *
   * @var \Drupal\Component\Utility\Random
   */
  public $random;

  /**
   * Initialize the driver manager.
   */
  public function __construct(array $drivers = array(), Random $random) {
    foreach ($drivers as $name => $driver) {
      $this->registerDriver($name, $driver);
    }
    $this->random = $random;
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

    $driver = $this->drivers[$name];

    // Bootstrap driver if needed.
    if (!$driver->isBootstrapped()) {
      $driver->bootstrap();
    }

    return $driver;
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

  /**
   * Returns all instantiated drivers.
   */
  public function getDrivers() {
    return $this->drivers;
  }

  /**
   * Sets the Behat Environment.
   */
  public function setEnvironment(Environment $environment) {
    $this->environment = $environment;
  }

  /**
   * Returns the Behat Environment.
   */
  public function getEnvironment() {
    return $this->environment;
  }

}
