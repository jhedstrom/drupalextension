<?php

namespace spec\Drupal\DrupalExtension\Context;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

use Behat\Testwork\Hook\HookDispatcher;
use Behat\Testwork\Hook\HookRepository;

use Drupal\DrupalDriverManager;

class RawDrupalContextSpec extends ObjectBehavior
{
    function it_should_be_drupal_aware()
    {
        $this->shouldHaveType('Drupal\DrupalExtension\Context\DrupalAwareInterface');
    }

    function it_can_set_and_get_drupal_manager(DrupalDriverManager $drupal)
    {
        $this->setDrupal($drupal);
        $this->getDrupal()->shouldBeAnInstanceOf('Drupal\DrupalDriverManager');
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

    function it_can_get_the_current_drupal_driver(DrupalDriverManager $drupal)
    {
        $this->setDrupal($drupal);
        $this->getDriver();
    }

}
