<?php

namespace spec\Drupal\DrupalExtension\Hook\Scope;

use Behat\Behat\Context\Context;
use Behat\Testwork\Environment\Environment;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class BeforeNodeCreateScopeSpec extends ObjectBehavior
{
    function let(Environment $environment, Context $context)
    {
        $node = new \stdClass();
        $this->beConstructedWith($environment, $context, $node);
    }

    function it_is_initializable()
    {
        $this->shouldHaveType('Drupal\DrupalExtension\Hook\Scope\BeforeNodeCreateScope');
    }

    function it_should_return_context()
    {
        $context = $this->getContext();
        $context->shouldBeAnInstanceOf('Behat\Behat\Context\Context');
    }

    function it_should_return_a_node()
    {
        $this->getEntity()->shouldBeAnInstanceOf('stdClass');
    }
}
