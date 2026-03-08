<?php

namespace spec\Drupal\DrupalExtension\Hook\Call;

use Drupal\DrupalExtension\Context\Annotation\Reader;
use Drupal\DrupalExtension\Hook\Call\BeforeTermCreate;
use PhpSpec\ObjectBehavior;

class BeforeTermCreateSpec extends ObjectBehavior
{
    function it_accepts_a_callable()
    {
        $this->beConstructedWith(null, [Reader::class, 'readCallee'], null);
        $this->shouldHaveType(BeforeTermCreate::class);
        $this->getName()->shouldReturn('BeforeTermCreate');
    }

    function it_accepts_an_array()
    {
        // Simulates the scenario where the context class is not yet
        // autoloadable and the callable array cannot be validated by PHP
        // as a true callable at construction time.
        $this->beConstructedWith(null, [Reader::class, 'readCallee'], null);
        $this->shouldHaveType(BeforeTermCreate::class);
    }

    function it_accepts_a_filter_string()
    {
        $this->beConstructedWith('tags', [Reader::class, 'readCallee'], null);
        $this->getFilterString()->shouldReturn('tags');
    }
}
