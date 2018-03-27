<?php

namespace spec\Drupal\DrupalExtension\Context;

use Drupal\DrupalExtension\Context\RandomContext;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class RandomContextSpec extends ObjectBehavior
{
    function it_is_initializable()
    {
        $this->shouldHaveType(RandomContext::class);
    }
}
