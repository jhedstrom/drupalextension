<?php

namespace spec\Drupal;

use Behat\Testwork\Environment\Environment;

use Drupal\Driver\DriverInterface;

use Drupal\DrupalDriverManagerInterface;
use PhpSpec\ObjectBehavior;

/**
 * Tests the DrupalDriverManager class.
 */
class DrupalDriverManagerSpec extends ObjectBehavior {

  public function it_is_initializable() {
    $this->shouldHaveType(DrupalDriverManagerInterface::class);
  }

  public function it_registers_drivers(DriverInterface $driver) {
    $driver->isBootstrapped()->willReturn(TRUE);
    $this->shouldThrow(\InvalidArgumentException::class)->duringGetDriver();
    $this->registerDriver('name', $driver);
    $this->shouldThrow(\InvalidArgumentException::class)->duringGetDriver();
    $this->setDefaultDriverName('name');

    $driver = $this->getDriver();
    $driver->shouldBeAnInstanceOf(DriverInterface::class);
    $this->shouldThrow(\InvalidArgumentException::class)->duringGetDriver('non-existent');
  }

  public function it_registers_drivers_via_constructor(DriverInterface $driver) {
    $driver->isBootstrapped()->willReturn(TRUE);
    $this->beConstructedWith(['My_Driver' => $driver]);
    $this->getDriver('my_driver')->shouldBeAnInstanceOf(DriverInterface::class);
    $this->getDrivers()->shouldHaveCount(1);
  }

  public function it_throws_when_setting_default_to_unregistered_driver() {
    $this->shouldThrow(\InvalidArgumentException::class)->duringSetDefaultDriverName('nonexistent');
  }

  public function it_returns_null_environment_by_default() {
    $this->getEnvironment()->shouldReturn(NULL);
  }

  public function it_sets_behat_environments(Environment $environment) {
    $this->setEnvironment($environment);
    $this->getEnvironment()->shouldBeAnInstanceOf(Environment::class);
  }

  public function it_returns_empty_drivers_by_default() {
    $this->getDrivers()->shouldHaveCount(0);
  }

  public function it_gets_all_drivers(DriverInterface $driver) {
    $this->registerDriver('one', $driver);
    $this->registerDriver('two', $driver);
    $this->getDrivers()->shouldHaveCount(2);
  }

  public function it_bootstraps_the_driver_if_needed(DriverInterface $driver) {
    $driver->isBootstrapped()->willReturn(FALSE);
    $driver->bootstrap()->shouldBeCalled();
    $this->registerDriver('a_driver', $driver);
    $this->getDriver('a_driver')->shouldBeAnInstanceOf(DriverInterface::class);
  }

  public function it_wont_bootstrap_the_driver_twice(DriverInterface $driver) {
    $driver->isBootstrapped()->willReturn(TRUE);
    $driver->bootstrap()->shouldNotBeCalled();
    $this->registerDriver('A_Driver', $driver);
    $this->getDriver('a_driver')->shouldBeAnInstanceOf(DriverInterface::class);
  }

}
