<?php

namespace spec\Drupal\DrupalExtension\ServiceContainer;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class DrupalExtensionSpec extends ObjectBehavior
{
    function it_is_a_testwork_extension()
    {
        $this->shouldHaveType('Behat\Testwork\ServiceContainer\Extension');
    }

    function it_is_named_drupal()
    {
        $this->getConfigKey()->shouldReturn('drupal');
    }
}
