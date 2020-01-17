<?php

namespace spec\Drupal\DrupalExtension\Context;

use Drupal\Driver\DriverInterface;
use Drupal\DrupalDriverManagerInterface;
use Drupal\DrupalExtension\Context\DrupalAwareInterface;
use PhpSpec\ObjectBehavior;

use Drupal\DrupalDriverManager;

class RawDrupalContextSpec extends ObjectBehavior
{
    function it_should_be_drupal_aware()
    {
        $this->shouldHaveType(DrupalAwareInterface::class);
    }

    function it_can_set_and_get_drupal_manager(DrupalDriverManagerInterface $drupal)
    {
        $this->setDrupal($drupal);
        $this->getDrupal()->shouldBeAnInstanceOf(DrupalDriverManagerInterface::class);
    }

    function it_can_set_and_get_drupal_parameters()
    {
        $parameters = array(
            'one' => '1',
            'two' => '2',
        );
        $this->setDrupalParameters($parameters);
        $this->getDrupalParameter('one')->shouldReturn('1');
        $this->getDrupalParameter('two')->shouldReturn('2');
    }

    function it_can_manage_text_values()
    {
        $parameters = array(
            'text' => array(
                'login' => 'Log in',
            ),
        );
        $this->setDrupalParameters($parameters);
        $this->getDrupalText('login')->shouldReturn('Log in');
        $this->shouldThrow('Exception')->duringGetDrupalText('No such string');
    }

    function it_can_get_the_current_drupal_driver(DrupalDriverManagerInterface $drupal, DriverInterface $driver)
    {
        $drupal->getDriver($this->an)->willReturn($driver);
        $this->setDrupal($drupal);
        $this->getDriver()->shouldBeAnInstanceOf(DriverInterface::class);
    }

}
