<?php

declare(strict_types=1);

namespace Drupal\DrupalExtension\Context;

use Behat\Transformation\Transform;
use Behat\Hook\BeforeScenario;
use Behat\Hook\AfterScenario;
use Behat\Behat\Hook\Scope\ScenarioScope;
use Behat\Gherkin\Node\TableNode;

/**
 * Transform tokens into random variables.
 */
class RandomContext extends RawDrupalContext {
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
   */
  #[Transform('#([^<]*\<\?.*\>[^>]*)#')]
  public function transformVariables(string $message): string|array|null {
    $patterns = [];
    $replacements = [];

    preg_match_all(static::VARIABLE_REGEX, $message, $matches);
    foreach ($matches[0] as $variable) {
      $replacements[] = $this->values[$variable];
      $patterns[] = '#' . preg_quote($variable) . '#';
    }

    return preg_replace($patterns, $replacements, $message);
  }

  /**
   * Transform random variables in table arguments.
   */
  #[Transform('table:*')]
  public function transformTable(TableNode $table): TableNode {
    $rows = [];
    foreach ($table->getRows() as $row) {
      $rows[] = array_map($this->transformVariables(...), $row);
    }
    return new TableNode($rows);
  }

  /**
   * Set values for each random variable found in the current scenario.
   */
  #[BeforeScenario]
  public function beforeScenarioSetVariables(ScenarioScope $scope): void {
    $steps = [];
    if ($scope->getFeature()->hasBackground()) {
      $steps = $scope->getFeature()->getBackground()->getSteps();
    }
    $steps = array_merge($steps, $scope->getScenario()->getSteps());
    foreach ($steps as $step) {
      preg_match_all(static::VARIABLE_REGEX, $step->getText(), $matches);
      $variablesFound = $matches[0];
      // Find variables in are TableNodes or PyStringNodes.
      $stepArgument = $step->getArguments();
      if (!empty($stepArgument) && $stepArgument[0] instanceof TableNode) {
        preg_match_all(static::VARIABLE_REGEX, $stepArgument[0]->getTableAsString(), $matches);
        $variablesFound = array_filter(array_merge($variablesFound, $matches[0]));
      }
      foreach ($variablesFound as $variableFound) {
        if (!isset($this->values[$variableFound])) {
          $value = $this->getDriver()->getRandom()->name(10);
          // Value forced to lowercase to ensure it is machine-readable.
          $this->values[$variableFound] = strtolower((string) $value);
        }
      }
    }
  }

  /**
   * Reset variables after the scenario.
   */
  #[AfterScenario]
  public function afterScenarioResetVariables(ScenarioScope $scope): void {
    $this->values = [];
  }

}
