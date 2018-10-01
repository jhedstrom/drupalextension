<?php

namespace Drupal\DrupalExtension;

use Behat\Behat\Hook\Scope\BeforeFeatureScope;
use Behat\Behat\Hook\Scope\BeforeScenarioScope;
use Behat\Behat\Hook\Scope\BeforeStepScope;
use Behat\Behat\Hook\Scope\StepScope;
use Behat\Gherkin\Node\ScenarioInterface;
use Behat\Testwork\Hook\Scope\HookScope;

/**
 * Helper methods to check the tags that are present on a feature or scenario.
 */
trait TagTrait
{

    /**
     * @var \Drupal\DrupalExtension\TagTraitHelper
     */
    protected $tagTraitHelper;

    /**
     * Tracks the scopes that can be tagged.
     *
     * Tags can be put on features as well as scenarios. This hook will fire
     * when a new feature or scenario is being executed and will keep track of
     * both so that the tags can be inspected during the test.
     *
     * @param \Behat\Testwork\Hook\Scope\HookScope $scope
     *
     * @BeforeScenario
     * @BeforeStep
     */
    public function registerTagContainingScopes(HookScope $scope)
    {
        if ($scope instanceof BeforeScenarioScope) {
            $this->getTagTraitHelper()->registerScenario($scope);
        }

        if ($scope instanceof BeforeStepScope) {
            $this->getTagTraitHelper()->registerFeature($scope);
        }
    }

    /**
     * Returns the helper class as a singleton.
     *
     * @return \Drupal\DrupalExtension\TagTraitHelper
     */
    protected function getTagTraitHelper()
    {
        if (empty($this->tagTraitHelper)) {
            $this->tagTraitHelper = new TagTraitHelper();
        }
        return $this->tagTraitHelper;
    }

    /**
     * Returns all tags for the current scenario and feature.
     *
     * @return string[]
     */
    protected function getTags()
    {
        $featureTags = $this->getTagTraitHelper()->getFeature()->getTags();
        $scenarioTags = $this->getTagTraitHelper()->getScenario()->getTags();
        return array_unique(array_merge($featureTags, $scenarioTags));
    }

    /**
     * Checks whether the current scenario or feature has the given tag.
     *
     * @param string $tag
     *
     * @return bool
     */
    protected function hasTag($tag)
    {
        return in_array($tag, $this->getTags());
    }
}
