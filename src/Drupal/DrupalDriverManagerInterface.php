<?php

namespace Drupal;

use Behat\Testwork\Environment\Environment;
use Drupal\Driver\DriverInterface;

interface DrupalDriverManagerInterface
{
    /**
     * Register a new driver.
     *
     * @param string $name
     *   Driver name.
     * @param \Drupal\Driver\DriverInterface $driver
     *   An instance of a DriverInterface.
     */
    public function registerDriver($name, DriverInterface $driver);

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
    public function getDriver($name = null);

    /**
     * Set the default driver name.
     *
     * @param string $name
     *   Default driver name to set.
     *
     * @throws \InvalidArgumentException
     *   Thrown when the driver is not registered.
     */
    public function setDefaultDriverName($name);

    /**
     * Returns all registered drivers.
     *
     * @return \Drupal\Driver\DriverInterface[]
     *   An array of drivers.
     */
    public function getDrivers();

    /**
     * Sets the Behat Environment.
     *
     * @param \Behat\Testwork\Environment\Environment $environment
     *   The Behat Environment to set.
     */
    public function setEnvironment(Environment $environment);

    /**
     * Returns the Behat Environment.
     *
     * @return \Behat\Testwork\Environment\Environment
     *   The Behat Environment.
     */
    public function getEnvironment();
}
