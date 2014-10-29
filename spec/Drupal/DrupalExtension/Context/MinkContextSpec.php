<?php

namespace spec\Drupal\DrupalExtension\Context;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class MinkContextSpec extends ObjectBehavior
{
    function it_extends_the_mink_context()
    {
        $this->shouldHaveType('Behat\MinkExtension\Context\MinkContext');
    }

    function it_is_a_translatable_context()
    {
        $this->shouldHaveType('Behat\Behat\Context\TranslatableContext');
    }
}
