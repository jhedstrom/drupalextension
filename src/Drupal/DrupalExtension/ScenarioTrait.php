<?php

namespace Drupal\DrupalExtension;

use Behat\Behat\Hook\Scope\BeforeScenarioScope;

/**
 * A workaround to discover the current scenario.
 *
 * @see https://github.com/Behat/Behat/issues/653
 * @see https://github.com/Behat/Behat/issues/650
 * The solution is documented in this issue: https://github.com/Behat/Behat/issues/703#issuecomment-86687563
 */
trait ScenarioTrait
{

    /**
     * The registered scenario.
     *
     * @var \Behat\Gherkin\Node\ScenarioInterface
     */
    protected $currentScenario;

    /**
     * Register the scenario.
     *
     * @param \Behat\Behat\Hook\Scope\BeforeScenarioScope $scope
     *
     * @BeforeScenario
     */
    public function registerScenario(BeforeScenarioScope $scope)
    {
        $this->currentScenario = $scope->getScenario();
    }

    /**
     * @return \Behat\Gherkin\Node\ScenarioInterface
     */
    protected function getScenario()
    {
        return $this->currentScenario;
    }
}
