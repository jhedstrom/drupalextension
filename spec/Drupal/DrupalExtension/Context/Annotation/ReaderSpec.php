<?php

namespace spec\Drupal\DrupalExtension\Context\Annotation;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use ReflectionMethod;

class ReaderSpec extends ObjectBehavior
{
    function it_is_initializable()
    {
        $this->shouldHaveType('Drupal\DrupalExtension\Context\Annotation\Reader');
    }
}
