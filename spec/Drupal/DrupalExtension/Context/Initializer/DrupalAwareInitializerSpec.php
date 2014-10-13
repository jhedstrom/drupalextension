<?php

namespace spec\Drupal\DrupalExtension\Context\Initializer;

use Behat\Behat\Context\Context;

use Drupal\DrupalDriverManager;
use Drupal\DrupalExtension\Context\DrupalAwareInterface;

use Behat\Testwork\Call\CallCenter;
use Behat\Testwork\Environment\EnvironmentManager;
use Behat\Testwork\Hook\HookDispatcher;
use Behat\Testwork\Hook\HookRepository;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class DrupalAwareInitializerSpec extends ObjectBehavior
{
    private $dispatcher;

    function let(DrupalDriverManager $drupal)
    {
        $callCenter = new CallCenter();
        $manager = new EnvironmentManager();
        $repository = new HookRepository($manager);
        // Cannot mock this class as it is marked as final.
        $this->dispatcher = new HookDispatcher($repository, $callCenter);
        $this->beConstructedWith($drupal, array(), $this->dispatcher);
    }

    function it_is_a_context_initializer()
    {
        $this->shouldHaveType('Behat\Behat\Context\Initializer\ContextInitializer');
    }

    function it_does_nothing_for_basic_contexts(Context $context)
    {
        $this->initializeContext($context);
    }

    function it_injects_drupal_and_parameters_and_dispatcher_in_drupal_aware_Contexts(DrupalAwareInterface $context, $drupal)
    {
        $context->setDispatcher($this->dispatcher)->shouldBeCAlled();
        $context->setDrupal($drupal)->shouldBeCAlled();
        $context->setDrupalParameters(array())->shouldBeCAlled();
        $this->initializeContext($context);
    }
}
