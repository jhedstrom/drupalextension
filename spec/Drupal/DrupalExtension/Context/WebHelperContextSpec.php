<?php

namespace spec\Drupal\DrupalExtension\Context;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class WebHelperContextSpec extends ObjectBehavior
{
    function it_extends_the_mink_context()
    {
        $this->shouldHaveType('Behat\MinkExtension\Context\MinkContext');
    }
}
