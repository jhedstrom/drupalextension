<?php

declare(strict_types=1);

namespace Drupal\DrupalExtension\Tests;

use Drupal\DrupalDriverManagerInterface;
use Behat\Testwork\Environment\Environment;
use Drupal\Driver\DriverInterface;
use Drupal\DrupalDriverManager;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * Tests the DrupalDriverManager class.
 */
#[CoversClass(DrupalDriverManager::class)]
class DrupalDriverManagerTest extends TestCase {

  /**
   * Tests that the manager implements the interface.
   */
  public function testImplementsInterface(): void {
    $manager = new DrupalDriverManager();
    $this->assertInstanceOf(DrupalDriverManagerInterface::class, $manager);
  }

  /**
   * Tests that constructor registers drivers.
   */
  public function testConstructorRegistersDrivers(): void {
    $driver = $this->createDriverMock(TRUE);
    $manager = new DrupalDriverManager(['Alpha' => $driver]);
    $this->assertSame($driver, $manager->getDriver('alpha'));
    $this->assertCount(1, $manager->getDrivers());
  }

  /**
   * Tests that constructor lowercases driver names.
   */
  public function testConstructorLowercasesDriverNames(): void {
    $driver = $this->createDriverMock(TRUE);
    $manager = new DrupalDriverManager(['MY_DRIVER' => $driver]);
    $this->assertSame($driver, $manager->getDriver('my_driver'));
  }

  /**
   * Tests that registerDriver lowercases the name.
   */
  public function testRegisterDriverLowercasesName(): void {
    $driver = $this->createDriverMock(TRUE);
    $manager = new DrupalDriverManager();
    $manager->registerDriver('FooBar', $driver);
    $this->assertSame($driver, $manager->getDriver('foobar'));
  }

  /**
   * Tests that getDriver returns the default driver.
   */
  public function testGetDriverReturnsDefaultDriver(): void {
    $driver = $this->createDriverMock(TRUE);
    $manager = new DrupalDriverManager();
    $manager->registerDriver('default', $driver);
    $manager->setDefaultDriverName('default');
    $this->assertSame($driver, $manager->getDriver());
  }

  /**
   * Tests that getDriver throws without a default.
   */
  public function testGetDriverThrowsWithoutDefault(): void {
    $manager = new DrupalDriverManager();
    $this->expectException(\InvalidArgumentException::class);
    $this->expectExceptionMessage('Specify a Drupal driver to get.');
    $manager->getDriver();
  }

  /**
   * Tests that getDriver throws for unregistered names.
   */
  public function testGetDriverThrowsForUnregisteredName(): void {
    $manager = new DrupalDriverManager();
    $this->expectException(\InvalidArgumentException::class);
    $this->expectExceptionMessage('Driver "ghost" is not registered');
    $manager->getDriver('ghost');
  }

  /**
   * Tests that getDriver bootstraps when needed.
   */
  public function testGetDriverBootstrapsWhenNeeded(): void {
    $driver = $this->createMock(DriverInterface::class);
    $driver->method('isBootstrapped')->willReturn(FALSE);
    $driver->expects($this->once())->method('bootstrap');
    $manager = new DrupalDriverManager(['test' => $driver]);
    $manager->getDriver('test');
  }

  /**
   * Tests that getDriver skips bootstrap when already bootstrapped.
   */
  public function testGetDriverSkipsBootstrapWhenAlreadyBootstrapped(): void {
    $driver = $this->createMock(DriverInterface::class);
    $driver->method('isBootstrapped')->willReturn(TRUE);
    $driver->expects($this->never())->method('bootstrap');
    $manager = new DrupalDriverManager(['test' => $driver]);
    $manager->getDriver('test');
  }

  /**
   * Tests that getDrivers returns empty by default.
   */
  public function testGetDriversReturnsEmptyByDefault(): void {
    $manager = new DrupalDriverManager();
    $this->assertSame([], $manager->getDrivers());
  }

  /**
   * Tests that getDrivers returns all registered drivers.
   */
  public function testGetDriversReturnsAllRegistered(): void {
    $driver_a = $this->createDriverMock(TRUE);
    $driver_b = $this->createDriverMock(TRUE);
    $manager = new DrupalDriverManager();
    $manager->registerDriver('a', $driver_a);
    $manager->registerDriver('b', $driver_b);
    $drivers = $manager->getDrivers();
    $this->assertCount(2, $drivers);
    $this->assertSame($driver_a, $drivers['a']);
    $this->assertSame($driver_b, $drivers['b']);
  }

  /**
   * Tests that setDefaultDriverName throws for unregistered drivers.
   */
  public function testSetDefaultDriverNameThrowsForUnregistered(): void {
    $manager = new DrupalDriverManager();
    $this->expectException(\InvalidArgumentException::class);
    $this->expectExceptionMessage('Driver "missing" is not registered.');
    $manager->setDefaultDriverName('missing');
  }

  /**
   * Tests that setDefaultDriverName lowercases the name.
   */
  public function testSetDefaultDriverNameLowercases(): void {
    $driver = $this->createDriverMock(TRUE);
    $manager = new DrupalDriverManager();
    $manager->registerDriver('mydriver', $driver);
    $manager->setDefaultDriverName('MyDriver');
    $this->assertSame($driver, $manager->getDriver());
  }

  /**
   * Tests that getEnvironment returns null by default.
   */
  public function testGetEnvironmentReturnsNullByDefault(): void {
    $manager = new DrupalDriverManager();
    $this->assertNull($manager->getEnvironment());
  }

  /**
   * Tests setting and getting the environment.
   */
  public function testSetAndGetEnvironment(): void {
    $environment = $this->createMock(Environment::class);
    $manager = new DrupalDriverManager();
    $manager->setEnvironment($environment);
    $this->assertSame($environment, $manager->getEnvironment());
  }

  /**
   * Creates a mock driver.
   */
  private function createDriverMock(bool $bootstrapped): DriverInterface {
    $driver = $this->createMock(DriverInterface::class);
    $driver->method('isBootstrapped')->willReturn($bootstrapped);
    return $driver;
  }

}
