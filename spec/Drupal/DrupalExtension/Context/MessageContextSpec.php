<?php

namespace spec\Drupal\DrupalExtension\Context;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class MessageContextSpec extends ObjectBehavior
{
    function it_is_drupal_aware()
    {
        $this->shouldHaveType('Drupal\DrupalExtension\Context\RawDrupalContext');
    }

    function it_is_a_translatable_context()
    {
        $this->shouldHaveType('Behat\Behat\Context\TranslatableContext');
    }
}
