<?php

declare(strict_types=1);

namespace Drupal\DrupalExtension\Tests;

use Drupal\DrupalDriverManagerInterface;
use Behat\Testwork\Environment\Environment;
use Drupal\Driver\DriverInterface;
use Drupal\DrupalDriverManager;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(DrupalDriverManager::class)]
class DrupalDriverManagerTest extends TestCase
{

    public function testImplementsInterface(): void
    {
        $manager = new DrupalDriverManager();
        $this->assertInstanceOf(DrupalDriverManagerInterface::class, $manager);
    }

    public function testConstructorRegistersDrivers(): void
    {
        $driver = $this->createDriverMock(true);
        $manager = new DrupalDriverManager(['Alpha' => $driver]);
        $this->assertSame($driver, $manager->getDriver('alpha'));
        $this->assertCount(1, $manager->getDrivers());
    }

    public function testConstructorLowercasesDriverNames(): void
    {
        $driver = $this->createDriverMock(true);
        $manager = new DrupalDriverManager(['MY_DRIVER' => $driver]);
        $this->assertSame($driver, $manager->getDriver('my_driver'));
    }

    public function testRegisterDriverLowercasesName(): void
    {
        $driver = $this->createDriverMock(true);
        $manager = new DrupalDriverManager();
        $manager->registerDriver('FooBar', $driver);
        $this->assertSame($driver, $manager->getDriver('foobar'));
    }

    public function testGetDriverReturnsDefaultDriver(): void
    {
        $driver = $this->createDriverMock(true);
        $manager = new DrupalDriverManager();
        $manager->registerDriver('default', $driver);
        $manager->setDefaultDriverName('default');
        $this->assertSame($driver, $manager->getDriver());
    }

    public function testGetDriverThrowsWithoutDefault(): void
    {
        $manager = new DrupalDriverManager();
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Specify a Drupal driver to get.');
        $manager->getDriver();
    }

    public function testGetDriverThrowsForUnregisteredName(): void
    {
        $manager = new DrupalDriverManager();
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Driver "ghost" is not registered');
        $manager->getDriver('ghost');
    }

    public function testGetDriverBootstrapsWhenNeeded(): void
    {
        $driver = $this->createMock(DriverInterface::class);
        $driver->method('isBootstrapped')->willReturn(false);
        $driver->expects($this->once())->method('bootstrap');
        $manager = new DrupalDriverManager(['test' => $driver]);
        $manager->getDriver('test');
    }

    public function testGetDriverSkipsBootstrapWhenAlreadyBootstrapped(): void
    {
        $driver = $this->createMock(DriverInterface::class);
        $driver->method('isBootstrapped')->willReturn(true);
        $driver->expects($this->never())->method('bootstrap');
        $manager = new DrupalDriverManager(['test' => $driver]);
        $manager->getDriver('test');
    }

    public function testGetDriversReturnsEmptyByDefault(): void
    {
        $manager = new DrupalDriverManager();
        $this->assertSame([], $manager->getDrivers());
    }

    public function testGetDriversReturnsAllRegistered(): void
    {
        $driverA = $this->createDriverMock(true);
        $driverB = $this->createDriverMock(true);
        $manager = new DrupalDriverManager();
        $manager->registerDriver('a', $driverA);
        $manager->registerDriver('b', $driverB);
        $drivers = $manager->getDrivers();
        $this->assertCount(2, $drivers);
        $this->assertSame($driverA, $drivers['a']);
        $this->assertSame($driverB, $drivers['b']);
    }

    public function testSetDefaultDriverNameThrowsForUnregistered(): void
    {
        $manager = new DrupalDriverManager();
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Driver "missing" is not registered.');
        $manager->setDefaultDriverName('missing');
    }

    public function testSetDefaultDriverNameLowercases(): void
    {
        $driver = $this->createDriverMock(true);
        $manager = new DrupalDriverManager();
        $manager->registerDriver('mydriver', $driver);
        $manager->setDefaultDriverName('MyDriver');
        $this->assertSame($driver, $manager->getDriver());
    }

    public function testGetEnvironmentReturnsNullByDefault(): void
    {
        $manager = new DrupalDriverManager();
        $this->assertNull($manager->getEnvironment());
    }

    public function testSetAndGetEnvironment(): void
    {
        $environment = $this->createMock(Environment::class);
        $manager = new DrupalDriverManager();
        $manager->setEnvironment($environment);
        $this->assertSame($environment, $manager->getEnvironment());
    }

    private function createDriverMock(bool $bootstrapped): DriverInterface
    {
        $driver = $this->createMock(DriverInterface::class);
        $driver->method('isBootstrapped')->willReturn($bootstrapped);
        return $driver;
    }
}
