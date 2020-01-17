<?php

namespace spec\Drupal\DrupalExtension\Hook\Scope;

use Behat\Behat\Context\Context;
use Behat\Testwork\Environment\Environment;

use Behat\Testwork\Suite\Suite;
use Drupal\DrupalExtension\Hook\Scope\BeforeNodeCreateScope;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class BeforeNodeCreateScopeSpec extends ObjectBehavior
{
    function let(Environment $environment, Context $context, Suite $suite)
    {
        $node = new \stdClass();
        $environment->getSuite()->willReturn($suite);
        $this->beConstructedWith($environment, $context, $node);
    }

    function it_is_initializable()
    {
        $this->shouldHaveType(BeforeNodeCreateScope::class);
    }

    function it_should_return_context()
    {
        $context = $this->getContext();
        $context->shouldBeAnInstanceOf(Context::class);
    }

    function it_should_return_a_node()
    {
        $this->getEntity()->shouldBeAnInstanceOf(\stdClass::class);
    }

    function it_should_return_environment()
    {
        $this->getEnvironment()->shouldBeAnInstanceOf(Environment::class);
    }

    function it_should_return_suite()
    {
        $this->getSuite()->shouldBeAnInstanceOf(Suite::class);
    }
}
