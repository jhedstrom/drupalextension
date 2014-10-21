<?php

namespace spec\Drupal\DrupalExtension\Context;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

use Drupal\DrupalDriverManager;

class DrushContextSpec extends ObjectBehavior
{
    function it_should_be_drupal_aware()
    {
        $this->shouldHaveType('Drupal\DrupalExtension\Context\RawDrupalContext');
    }

    function it_defaults_to_no_drush_output()
    {
        $this->shouldThrow('\Behat\Behat\Tester\Exception\PendingException')->duringReadDrushOutput();
    }
}
