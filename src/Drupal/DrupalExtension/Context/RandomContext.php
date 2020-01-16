<?php

namespace Drupal\DrupalExtension\Context;

use Behat\Behat\Hook\Scope\ScenarioScope;

/**
 * Class RandomContext
 * @package Drupal\DrupalExtension\Context
 *
 * Transform tokens into random variables.
 */
class RandomContext extends RawDrupalContext
{
    /**
     * Tracks variable names for consistent replacement during a given scenario.
     *
     * @var array
     */
    protected $values = [];

    /**
     * The regex to use for variable replacement.
     *
     * This matches placeholders in steps of the form `Given a string <?random>`.
     */
    const VARIABLE_REGEX = '#(\<\?.*?\>)#';

    /**
     * Transform random variables.
     *
     * @Transform #([^<]*\<\?.*\>[^>]*)#
     */
    public function transformVariables($message)
    {
        $patterns = [];
        $replacements = [];

        preg_match_all(static::VARIABLE_REGEX, $message, $matches);
        foreach ($matches[0] as $variable) {
            $replacements[] = $this->values[$variable];
            $patterns[] = '#' . preg_quote($variable) . '#';
        }
        $message = preg_replace($patterns, $replacements, $message);

        return $message;
    }

    /**
     * Set values for each random variable found in the current scenario.
     *
     * @BeforeScenario
     */
    public function beforeScenarioSetVariables(ScenarioScope $scope)
    {
        $steps = [];
        if ($scope->getFeature()->hasBackground()) {
            $steps = $scope->getFeature()->getBackground()->getSteps();
        }
        $steps = array_merge($steps, $scope->getScenario()->getSteps());
        foreach ($steps as $step) {
            preg_match_all(static::VARIABLE_REGEX, $step->getText(), $matches);
            $variables_found = $matches[0];
            // Find variables in are TableNodes or PyStringNodes.
            $step_argument = $step->getArguments();
            if (!empty($step_argument) && $step_argument[0] instanceof TableNode) {
                preg_match_all(static::VARIABLE_REGEX, $step_argument[0]->getTableAsString(), $matches);
                $variables_found = array_filter(array_merge($variables_found, $matches[0]));
            }
            foreach ($variables_found as $variable_name) {
                if (!isset($this->values[$variable_name])) {
                    $value = $this->getDriver()->getRandom()->name(10);
                    // Value forced to lowercase to ensure it is machine-readable.
                    $this->values[$variable_name] = strtolower($value);
                }
            }
        }
    }

    /**
     * Reset variables after the scenario.
     *
     * @AfterScenario
     */
    public function afterScenarioResetVariables(ScenarioScope $scope)
    {
        $this->values = [];
    }
}
