<?php

namespace spec\Drupal\DrupalExtension\ServiceContainer;

use Behat\Testwork\ServiceContainer\Extension;
use PhpSpec\ObjectBehavior;

class DrupalExtensionSpec extends ObjectBehavior
{
    function it_is_a_testwork_extension()
    {
        $this->shouldHaveType(Extension::class);
    }

    function it_is_named_drupal()
    {
        $this->getConfigKey()->shouldReturn('drupal');
    }
}
