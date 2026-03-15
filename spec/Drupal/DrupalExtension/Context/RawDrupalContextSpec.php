<?php

namespace spec\Drupal\DrupalExtension\Context;

use Drupal\Driver\DriverInterface;
use Drupal\DrupalDriverManagerInterface;
use Drupal\DrupalExtension\Context\DrupalAwareInterface;
use PhpSpec\ObjectBehavior;

/**
 * Tests the RawDrupalContext class.
 */
class RawDrupalContextSpec extends ObjectBehavior {

  public function it_should_be_drupal_aware() {
    $this->shouldHaveType(DrupalAwareInterface::class);
  }

  public function it_can_set_and_get_drupal_manager(DrupalDriverManagerInterface $drupal) {
    $this->setDrupal($drupal);
    $this->getDrupal()->shouldBeAnInstanceOf(DrupalDriverManagerInterface::class);
  }

  public function it_can_set_and_get_drupal_parameters() {
    $parameters = [
      'one' => '1',
      'two' => '2',
    ];
    $this->setDrupalParameters($parameters);
    $this->getDrupalParameter('one')->shouldReturn('1');
    $this->getDrupalParameter('two')->shouldReturn('2');
  }

  public function it_can_manage_text_values() {
    $parameters = [
      'text' => [
        'login' => 'Log in',
      ],
    ];
    $this->setDrupalParameters($parameters);
    $this->getDrupalText('login')->shouldReturn('Log in');
    $this->shouldThrow('Exception')->duringGetDrupalText('No such string');
  }

  public function it_can_get_the_current_drupal_driver(DrupalDriverManagerInterface $drupal, DriverInterface $driver) {
    $drupal->getDriver($this->an)->willReturn($driver);
    $this->setDrupal($drupal);
    $this->getDriver()->shouldBeAnInstanceOf(DriverInterface::class);
  }

}
