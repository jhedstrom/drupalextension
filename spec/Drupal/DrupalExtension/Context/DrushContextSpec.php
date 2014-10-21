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

    function it_will_catch_scenarios_without_any_output()
    {
        $this->shouldThrow('\RuntimeException')->duringReadDrushOutput();
    }

    function it_is_a_translatable_context()
    {
        $this->shouldHaveType('Behat\Behat\Context\TranslatableContext');
    }
}
