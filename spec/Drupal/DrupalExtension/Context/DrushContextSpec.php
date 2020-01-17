<?php

namespace spec\Drupal\DrupalExtension\Context;

use Behat\Behat\Context\TranslatableContext;
use Drupal\Driver\DrushDriver;
use Drupal\DrupalDriverManager;
use Drupal\DrupalExtension\Context\RawDrupalContext;
use PhpSpec\ObjectBehavior;

class DrushContextSpec extends ObjectBehavior
{
    function it_should_be_drupal_aware()
    {
        $this->shouldHaveType(RawDrupalContext::class);
    }

    function it_will_catch_scenarios_without_any_output()
    {
        $this->shouldThrow(\RuntimeException::class)->duringReadDrushOutput();
    }

    function it_is_a_translatable_context()
    {
        $this->shouldHaveType(TranslatableContext::class);
    }
}
