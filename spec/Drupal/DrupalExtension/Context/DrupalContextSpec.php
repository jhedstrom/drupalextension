<?php

namespace spec\Drupal\DrupalExtension\Context;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class DrupalContextSpec extends ObjectBehavior
{
    function it_is_initializable()
    {
        $this->shouldHaveType('Drupal\DrupalExtension\Context\DrupalContext');
    }

    function it_is_a_translatable_context()
    {
        $this->shouldHaveType('Behat\Behat\Context\TranslatableContext');
    }

}
