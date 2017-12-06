<?php

/**
 * @file
 * Contains \Drupal\DrupalDriverManager.
 */

namespace Drupal;

use Behat\Testwork\Environment\Environment;
use Drupal\Driver\DriverInterface;

/**
 * Drupal driver manager.
 */
class DrupalDriverManager
{

  /**
   * The name of the default driver.
   *
   * @var string
   */
    private $defaultDriverName;

  /**
   * All registered drivers.
   *
   * @var \Drupal\Driver\DriverInterface[]
   */
    private $drivers = array();

  /**
   * Behat environment.
   *
   * @var \Behat\Testwork\Environment\Environment
   */
    private $environment;

  /**
   * Initialize the driver manager.
   *
   * @param \Drupal\Driver\DriverInterface[] $drivers
   *   An array of drivers to register.
   */
    public function __construct(array $drivers = array())
    {
        foreach ($drivers as $name => $driver) {
            $this->registerDriver($name, $driver);
        }
    }

  /**
   * Register a new driver.
   *
   * @param string $name
   *   Driver name.
   * @param \Drupal\Driver\DriverInterface $driver
   *   An instance of a DriverInterface.
   */
    public function registerDriver($name, DriverInterface $driver)
    {
        $name = strtolower($name);
        $this->drivers[$name] = $driver;
    }

  /**
   * Return a registered driver by name, or the default driver.
   *
   * @param string $name
   *   The name of the driver to return. If omitted the default driver is
   *   returned.
   *
   * @return \Drupal\Driver\DriverInterface
   *   The requested driver.
   *
   * @throws \InvalidArgumentException
   *   Thrown when the requested driver is not registered.
   */
    public function getDriver($name = null)
    {
        $name = strtolower($name) ?: $this->defaultDriverName;

        if (null === $name) {
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
   *   Default driver name to set.
   *
   * @throws \InvalidArgumentException
   *   Thrown when the driver is not registered.
   */
    public function setDefaultDriverName($name)
    {
        $name = strtolower($name);

        if (!isset($this->drivers[$name])) {
            throw new \InvalidArgumentException(sprintf('Driver "%s" is not registered.', $name));
        }

        $this->defaultDriverName = $name;
    }

  /**
   * Returns all registered drivers.
   *
   * @return \Drupal\Driver\DriverInterface[]
   *   An array of drivers.
   */
    public function getDrivers()
    {
        return $this->drivers;
    }

  /**
   * Sets the Behat Environment.
   *
   * @param \Behat\Testwork\Environment\Environment $environment
   *   The Behat Environment to set.
   */
    public function setEnvironment(Environment $environment)
    {
        $this->environment = $environment;
    }

  /**
   * Returns the Behat Environment.
   *
   * @return \Behat\Testwork\Environment\Environment
   *   The Behat Environment.
   */
    public function getEnvironment()
    {
        return $this->environment;
    }
}
