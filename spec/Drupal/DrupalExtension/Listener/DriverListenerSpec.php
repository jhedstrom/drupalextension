<?php

namespace spec\Drupal\DrupalExtension\Listener;

use Behat\Behat\EventDispatcher\Event\ScenarioTested;
use Behat\Gherkin\Node\FeatureNode;
use Behat\Gherkin\Node\ScenarioNode;
use Behat\Testwork\Environment\Environment;
use Behat\Testwork\Suite\Suite;

use Drupal\DrupalDriverManager;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class DriverListenerSpec extends ObjectBehavior
{
    function let(DrupalDriverManager $drupal, ScenarioTested $event, FeatureNode $feature, ScenarioNode $scenario, Suite $suite, Environment $environment)
    {
        $parameters = array(
            'default_driver' => 'blackbox',
            'api_driver' => 'drupal_driver',
        );
        $this->beConstructedWith($drupal, $parameters);

        $event->getFeature()->willReturn($feature);
        $event->getScenario()->willReturn($scenario);
        $event->getEnvironment()->willReturn($environment);

        $feature->getTags()->willReturn(array('api'));
        $feature->hasTag('api')->willReturn(TRUE);

        $scenario->getTags()->willReturn(array());
    }

    function it_should_be_an_event_subscriber()
    {
        $this->shouldHaveType('Symfony\Component\EventDispatcher\EventSubscriberInterface');
    }

    function it_resets_the_default_drupal_driver_before_scenarios($event, $drupal, $environment, $feature, $scenario)
    {
        $drupal->setDefaultDriverName('drupal_driver')->shouldBeCalled();
        $drupal->setEnvironment($environment)->shouldBeCalled();
        $event->getEnvironment()->shouldBeCalled();
        $event->getFeature()->shouldBeCalled();
        $event->getScenario()->shouldBeCalled();
        $feature->getTags()->shouldBeCalled();
        $scenario->getTags()->shouldBeCalled();
        $this->prepareDefaultDrupalDriver($event);
    }

    function it_subscribes_to_scenarios_and_outlines()
    {
        $subscribedEvents = array(
            'tester.scenario_tested.before' => array('prepareDefaultDrupalDriver', 11),
            'tester.example_tested.before' => array('prepareDefaultDrupalDriver', 11),
        );
        $this->getSubscribedEvents()->shouldReturn($subscribedEvents);;
    }
}
