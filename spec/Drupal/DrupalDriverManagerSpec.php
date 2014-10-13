<?php

namespace spec\Drupal;

use Behat\Testwork\Environment\Environment;

use Drupal\Driver\DriverInterface;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class DrupalDriverManagerSpec extends ObjectBehavior
{
    function it_is_initializable()
    {
        $this->shouldHaveType('Drupal\DrupalDriverManager');
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

}
