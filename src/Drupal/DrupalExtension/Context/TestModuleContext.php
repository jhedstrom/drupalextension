<?php

namespace Drupal\DrupalExtension\Context;

use Behat\Behat\Hook\Scope\BeforeScenarioScope;
use Behat\Behat\Hook\Scope\ScenarioScope;

/**
 * Provides the ability to automatically enable and disable test modules.
 */
class TestModuleContext extends RawDrupalContext
{
  /**
   * Gets all with-module tags from a scenario scope.
   *
   * @param \Behat\Behat\Hook\Scope\ScenarioScope $scope
   *   The scenario scope.
   *
   * @return string[]
   *   The machine names of the modules to enable during the scenario.
   */
  protected function getTestModulesFromTags(ScenarioScope $scope)
  {
    $modules = array();
    $tags = array_merge($scope->getFeature()->getTags(), $scope->getScenario()->getTags());

    foreach ($tags as $tag) {
      if (strpos($tag, 'with-module:') === 0) {
        array_push($modules, substr($tag, 12));
      }
    }
    return array_unique($modules);
  }

  /**
   * Installs test modules for this scenario.
   *
   * @param \Behat\Behat\Hook\Scope\BeforeScenarioScope $scope
   *   The scenario scope.
   *
   * @BeforeScenario
   */
  public function installTestModules(BeforeScenarioScope $scope)
  {
    foreach ($this->getTestModulesFromTags($scope) as $module) {
      $this->getDriver()->moduleInstall($module);
    }
  }

  /**
   * Unistalls test modules for this scenario.
   *
   * @param \Behat\Behat\Hook\Scope\BeforeScenarioScope $scope
   *   The scenario scope.
   *
   * @AfterScenario
   */
  public function uninstallTestModules(BeforeScenarioScope $scope)
  {
    foreach ($this->getTestModulesFromTags($scope) as $module) {
      $this->getDriver()->moduleUninstall($module);
    }
  }
}
