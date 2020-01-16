<?php

namespace spec\Drupal\DrupalExtension\Context;

use Behat\Behat\Hook\Scope\ScenarioScope;
use Behat\Gherkin\Node\FeatureNode;
use Behat\Gherkin\Node\ScenarioInterface;
use Behat\Gherkin\Node\StepNode;
use Drupal\Component\Utility\Random;
use Drupal\Driver\Cores\CoreInterface;
use Drupal\Driver\DrupalDriver;
use Drupal\DrupalDriverManager;
use Drupal\DrupalExtension\Context\RandomContext;
use PhpSpec\ObjectBehavior;

class RandomContextSpec extends ObjectBehavior
{
    function let(DrupalDriverManager $drupal, DrupalDriver $driver, CoreInterface $core, Random $random)
    {
        $random->name(10)->willReturn('known_replacement');
        $driver->getRandom()->willReturn($random);
        $driver->getCore()->willReturn($core);
        $drupal->getDriver(NULL)->willReturn($driver);
        $this->setDrupal($drupal);
    }

    function it_is_initializable()
    {
        $this->shouldHaveType(RandomContext::class);
    }

    function it_converts_placeholders_to_random_strings(ScenarioScope $scope, ScenarioInterface $scenario, FeatureNode $feature, StepNode $step1, StepNode $step2)
    {
        $step1->getText()->willReturn('Given a <?random 123> string');
        $step2->getText()->willReturn('Then the <?random 123> placeholder will be replaced');
        $step1->getArguments()->shouldBeCalled();
        $step2->getArguments()->shouldBeCalled();
        $steps = [$step1, $step2];
        $scenario->getSteps()->willReturn($steps);
        $scope->getScenario()->willReturn($scenario);
        $scope->getFeature()->willReturn($feature);
        $this->beforeScenarioSetVariables($scope);

        $this->transformVariables('Given a <?random 123> string')
            ->shouldBe('Given a known_replacement string');
        $this->transformVariables('Then the <?random 123> placeholder will be replaced')
            ->shouldBe('Then the known_replacement placeholder will be replaced');
    }
}
