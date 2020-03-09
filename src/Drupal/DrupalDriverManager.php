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
class DrupalDriverManager implements DrupalDriverManagerInterface
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
    private $drivers = [];

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
    public function __construct(array $drivers = [])
    {
        foreach ($drivers as $name => $driver) {
            $this->registerDriver($name, $driver);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function registerDriver($name, DriverInterface $driver)
    {
        $name = strtolower($name);
        $this->drivers[$name] = $driver;
    }

    /**
     * {@inheritdoc}
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
     * {@inheritdoc}
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
     * {@inheritdoc}
     */
    public function getDrivers()
    {
        return $this->drivers;
    }

    /**
     * {@inheritdoc}
     */
    public function setEnvironment(Environment $environment)
    {
        $this->environment = $environment;
    }

    /**
     * {@inheritdoc}
     */
    public function getEnvironment()
    {
        return $this->environment;
    }
}
