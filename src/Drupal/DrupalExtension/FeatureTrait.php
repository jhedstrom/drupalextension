<?php

namespace Drupal\DrupalExtension;

use Behat\Behat\Hook\Scope\BeforeStepScope;

/**
 * A workaround to discover the current feature.
 *
 * @see https://github.com/Behat/Behat/issues/653
 * @see https://github.com/Behat/Behat/issues/650
 * The solution is documented in this issue: https://github.com/Behat/Behat/issues/703#issuecomment-86687563
 */
trait FeatureTrait
{

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
     *
     * @param \Behat\Behat\Hook\Scope\BeforeStepScope $scope
     *
     * @BeforeStep
     */
    public function registerFeature(BeforeStepScope $scope)
    {
        $this->currentFeature = $scope->getFeature();
    }

    /**
     * @return \Behat\Gherkin\Node\FeatureNode
     */
    protected function getFeature()
    {
        return $this->currentFeature;
    }
}
