<?php

declare(strict_types=1);

namespace Drupal\DrupalExtension;

use Behat\Hook\BeforeStep;
use Behat\Behat\Hook\Scope\BeforeStepScope;

/**
 * A workaround to discover the current feature.
 *
 * @see https://github.com/Behat/Behat/issues/653
 * @see https://github.com/Behat/Behat/issues/650
 * The solution is documented in this issue: https://github.com/Behat/Behat/issues/703#issuecomment-86687563
 */
trait FeatureTrait {

  /**
   * The registered feature.
   *
   * @var \Behat\Gherkin\Node\FeatureNode
   */
  protected $currentFeature;

  /**
   * Register the feature.
   *
   * This fires on a BeforeStep rather than a BeforeFeature since the latter
   * can only be called statically.
   */
  #[BeforeStep]
  public function registerFeature(BeforeStepScope $scope): void {
    $this->currentFeature = $scope->getFeature();
  }

  /**
   * Returns the current feature.
   *
   * @return \Behat\Gherkin\Node\FeatureNode
   *   The current feature node.
   */
  protected function getFeature() {
    return $this->currentFeature;
  }

}
