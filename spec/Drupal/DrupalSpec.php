<?php

namespace spec\Drupal;

use Behat\Testwork\Environment\Environment;

use Drupal\Component\Utility\Random;
use Drupal\Driver\DriverInterface;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class DrupalSpec extends ObjectBehavior
{
    function let(Random $random)
    {
        $this->beConstructedWith(array(), $random);
    }

    function it_is_initializable()
    {
        $this->shouldHaveType('Drupal\Drupal');
    }

    function it_registers_drivers(DriverInterface $driver)
    {
        $this->registerDriver('name', $driver);
        $this->setDefaultDriverName('name');

        $driver = $this->getDriver();
        $driver->shouldBeAnInstanceOf('Drupal\Driver\DriverInterface');
    }

    function it_sets_behat_environments(Environment $environment)
    {
        $this->setEnvironment($environment);

        $env = $this->getEnvironment();
        $env->shouldBeAnInstanceOf('Behat\Testwork\Environment\Environment');
    }

    function it_has_a_random_generator()
    {
        $random = $this->getRandom();
        $random->shouldBeAnInstanceOf('Drupal\Component\Utility\Random');
    }
}
