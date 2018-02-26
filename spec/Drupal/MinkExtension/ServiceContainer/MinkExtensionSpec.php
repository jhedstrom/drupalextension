<?php

namespace spec\Drupal\MinkExtension\ServiceContainer;

use Behat\Testwork\ServiceContainer\Extension;
use PhpSpec\ObjectBehavior;

class MinkExtensionSpec extends ObjectBehavior
{
    function it_is_a_testwork_extension()
    {
        $this->shouldHaveType(Extension::class);
    }

    function it_is_named_mink()
    {
        $this->getConfigKey()->shouldReturn('mink');
    }
}
