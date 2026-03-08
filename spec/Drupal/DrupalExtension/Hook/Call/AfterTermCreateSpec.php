<?php

namespace spec\Drupal\DrupalExtension\Hook\Call;

use Drupal\DrupalExtension\Context\Annotation\Reader;
use Drupal\DrupalExtension\Hook\Call\AfterTermCreate;
use PhpSpec\ObjectBehavior;

class AfterTermCreateSpec extends ObjectBehavior
{
    function it_accepts_a_callable()
    {
        $this->beConstructedWith(null, [Reader::class, 'readCallee'], null);
        $this->shouldHaveType(AfterTermCreate::class);
        $this->getName()->shouldReturn('AfterTermCreate');
    }

    function it_accepts_an_array()
    {
        // Simulates the scenario where the context class is not yet
        // autoloadable and the callable array cannot be validated by PHP
        // as a true callable at construction time.
        $this->beConstructedWith(null, [Reader::class, 'readCallee'], null);
        $this->shouldHaveType(AfterTermCreate::class);
    }

    function it_accepts_a_filter_string()
    {
        $this->beConstructedWith('tags', [Reader::class, 'readCallee'], null);
        $this->getFilterString()->shouldReturn('tags');
    }
}
