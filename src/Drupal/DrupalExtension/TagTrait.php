<?php

declare(strict_types=1);

namespace Drupal\DrupalExtension;

/**
 * Helper methods to check the tags that are present on a feature or scenario.
 */
trait TagTrait {
  use FeatureTrait, ScenarioTrait;

  /**
   * Returns all tags for the current scenario and feature.
   *
   * @return string[]
   *   An array of tag strings.
   */
  protected function getTags(): array {
    $featureTags = $this->getFeature()->getTags();
    $scenarioTags = $this->getScenario()->getTags();
    return array_unique(array_merge($featureTags, $scenarioTags));
  }

  /**
   * Checks whether the current scenario or feature has the given tag.
   */
  protected function hasTag(string $tag): bool {
    return in_array($tag, $this->getTags());
  }

}
