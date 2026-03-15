<?php

declare(strict_types=1);

namespace Drupal\DrupalExtension;

use Behat\Behat\Hook\Scope\BeforeScenarioScope;
use Behat\Behat\Hook\Scope\StepScope;

/**
 * A workaround to discover the current scenario.
 *
 * @see https://github.com/Behat/Behat/issues/653
 * @see https://github.com/Behat/Behat/issues/650
 *
 * The solution is documented in this issue: https://github.com/Behat/Behat/issues/703#issuecomment-86687563
 *
 * @deprecated in drupal:5.x and is removed from drupal:6.x. Use \Drupal\DrupalExtension\TagTrait instead.
 *
 * @see \Drupal\DrupalExtension\TagTrait
 */
trait ScenarioTagTrait {

  /**
   * The registered scenario.
   *
   * @var \Behat\Gherkin\Node\ScenarioInterface
   */
  protected $currentScenario;

  /**
   * Register the scenario.
   *
   * @BeforeScenario
   */
  public function registerScenario(BeforeScenarioScope $scope): void {
    $this->currentScenario = $scope->getScenario();
  }

  /**
   * Returns the current scenario.
   *
   * @return \Behat\Gherkin\Node\ScenarioInterface
   *   The current scenario.
   */
  protected function getScenario() {
    return $this->currentScenario;
  }

  /**
   * Get all tags for the current scenario.
   *
   * @return string[]
   *   An array of tag strings.
   */
  protected function getCurrentScenarioTags(StepScope $scope): array {
    $featureTags = $scope->getFeature()->getTags();
    $scenarioTags = $this->getScenario()->getTags();
    return array_merge($featureTags, $scenarioTags);
  }

}
