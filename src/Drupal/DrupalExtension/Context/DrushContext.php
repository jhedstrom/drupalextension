<?php

namespace Drupal\DrupalExtension\Context;

use Behat\Behat\Context\TranslatableContext;

/**
 * Provides step definitions for interacting directly with Drush commands.
 */
class DrushContext extends RawDrupalContext implements TranslatableContext {

  /**
   * Keep track of drush output.
   *
   * @var string|boolean
   */
  protected $drushOutput;

  /**
   * {@inheritDoc}
   */
  public static function getTranslationResources() {
    return glob(__DIR__ . '/../../../../i18n/*.xliff');
  }

  /**
   * Return the most recent drush command output.
   *
   * @return string
   */
  public function readDrushOutput() {
    if (!isset($this->drushOutput)) {
      throw new \RuntimeException('No drush output was found.');
    }
    return $this->drushOutput;
  }

  /**
   * @Given I run drush :command
   */
  public function assertDrushCommand($command) {
    if (!$this->drushOutput = $this->getDriver('drush')->$command()) {
       $this->drushOutput = TRUE;
    }
  }

  /**
   * @Given I run drush :command :arguments
   */
  public function assertDrushCommandWithArgument($command, $arguments) {
    $this->drushOutput = $this->getDriver('drush')->$command($this->fixStepArgument($arguments));
    if (!isset($this->drushOutput)) {
      $this->drushOutput = TRUE;
    }
  }

  /**
   * @Then drush output should contain :output
   */
  public function assertDrushOutput($output) {
    if (strpos((string) $this->readDrushOutput(), $this->fixStepArgument($output)) === FALSE) {
      throw new \Exception(sprintf("The last drush command output did not contain '%s'.\nInstead, it was:\n\n%s'", $output, $this->drushOutput));
    }
  }

  /**
   * @Then drush output should match :regex
   */
  public function assertDrushOutputMatches($regex) {
    if (!preg_match($regex, (string) $this->readDrushOutput())) {
      throw new \Exception(sprintf("The pattern %s was not found anywhere in the drush output.\nOutput:\n\n%s", $regex, $this->drushOutput));
    }
  }

  /**
   * @Then drush output should not contain :output
   */
  public function drushOutputShouldNotContain($output) {
    if (strpos((string) $this->readDrushOutput(), $this->fixStepArgument($output)) !== FALSE) {
        throw new \Exception(sprintf("The last drush command output did contain '%s' although it should not.\nOutput:\n\n%s'", $output, $this->drushOutput));
    }
  }

  /**
   * @Then print last drush output
   */
  public function printLastDrushOutput() {
    echo $this->readDrushOutput();
  }

  /**
   * Returns fixed step argument (with \\" replaced back to ").
   *
   * @param string $argument
   *
   * @return string
   */
  protected function fixStepArgument($argument) {
    return str_replace('\\"', '"', $argument);
  }

}
