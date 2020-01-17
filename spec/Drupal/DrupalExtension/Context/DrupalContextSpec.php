<?php

namespace spec\Drupal\DrupalExtension\Context;

use Behat\Behat\Context\TranslatableContext;
use Drupal\DrupalExtension\Context\RawDrupalContext;
use PhpSpec\ObjectBehavior;

class DrupalContextSpec extends ObjectBehavior
{
    function it_is_drupal_aware()
    {
        $this->shouldHaveType(RawDrupalContext::class);
    }

    function it_is_a_translatable_context()
    {
        $this->shouldHaveType(TranslatableContext::class);
    }

}
