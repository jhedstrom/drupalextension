<?php

namespace Drupal\DrupalExtension;

use Behat\Behat\Hook\Scope\BeforeScenarioScope;
use Behat\Behat\Hook\Scope\StepScope;
use Behat\Gherkin\Node\ScenarioInterface;

/**
 * A workaround to discover the current scenario.
 *
 * @see https://github.com/Behat/Behat/issues/653
 * @see https://github.com/Behat/Behat/issues/650
 *
 * The solution is documented in this issue: https://github.com/Behat/Behat/issues/703#issuecomment-86687563
 *
 * @package Drupal\DrupalExtension
 */
trait ScenarioTagTrait
{

    /**
     * The registered scenario.
     *
     * @var ScenarioInterface
     */
    protected $currentScenario;

    /**
     * Register the scenario.
     *
     * @param BeforeScenarioScope $scope
     *
     * @BeforeScenario
     */
    public function registerScenario(BeforeScenarioScope $scope)
    {
        $this->currentScenario = $scope->getScenario();
    }

    /**
     * @return ScenarioInterface
     */
    protected function getScenario()
    {
        return $this->currentScenario;
    }

    /**
     * Get all tags for the current scenario.
     *
     * @param StepScope $scope
     * @return string[]
     */
    protected function getCurrentScenarioTags(StepScope $scope)
    {
        $featureTags = $scope->getFeature()->getTags();
        $scenarioTags = $this->getScenario()->getTags();
        return array_merge($featureTags, $scenarioTags);
    }
}
