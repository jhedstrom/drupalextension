<?php

declare(strict_types=1);

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
class DrupalDriverManager implements DrupalDriverManagerInterface {

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
  private array $drivers = [];

  /**
   * Behat environment.
   */
  private ?Environment $environment = NULL;

  /**
   * Initialize the driver manager.
   *
   * @param \Drupal\Driver\DriverInterface[] $drivers
   *   An array of drivers to register.
   */
  public function __construct(array $drivers = []) {
    foreach ($drivers as $name => $driver) {
      $this->registerDriver($name, $driver);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function registerDriver($name, DriverInterface $driver): void {
    $name = strtolower($name);
    $this->drivers[$name] = $driver;
  }

  /**
   * {@inheritdoc}
   */
  public function getDriver($name = NULL) {
    $name = NULL === $name ? $this->defaultDriverName : strtolower($name);

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
   * {@inheritdoc}
   */
  public function getDrivers(): array {
    return $this->drivers;
  }

  /**
   * {@inheritdoc}
   */
  public function setDefaultDriverName($name): void {
    $name = strtolower($name);

    if (!isset($this->drivers[$name])) {
      throw new \InvalidArgumentException(sprintf('Driver "%s" is not registered.', $name));
    }

    $this->defaultDriverName = $name;
  }

  /**
   * {@inheritdoc}
   */
  public function getEnvironment(): ?Environment {
    return $this->environment;
  }

  /**
   * {@inheritdoc}
   */
  public function setEnvironment(Environment $environment): void {
    $this->environment = $environment;
  }

}
