<?php

declare(strict_types=1);

namespace Drupal\DrupalExtension\Context;

use Behat\Step\Given;
use Behat\Step\Then;
use Behat\Behat\Context\TranslatableContext;

/**
 * Provides step definitions for interacting directly with Drush commands.
 */
class DrushContext extends RawDrupalContext implements TranslatableContext {

  /**
   * Keep track of drush output.
   *
   * @var string|bool
   */
  protected $drushOutput;

  /**
   * {@inheritdoc}
   */
  public static function getTranslationResources() {
    return self::getDrupalTranslationResources();
  }

  /**
   * Return the most recent drush command output.
   *
   * @return string
   *   The most recent drush command output.
   */
  public function readDrushOutput() {
    if ($this->drushOutput === NULL) {
      throw new \RuntimeException('No drush output was found.');
    }
    return $this->drushOutput;
  }

  /**
   * Run a Drush command.
   *
   * @code
   * Given I run drush "status"
   * @endcode
   */
  #[Given('I run drush :command')]
  public function assertDrushCommand(string $command): void {
    if (!$this->drushOutput = $this->getDriver('drush')->$command()) {
      $this->drushOutput = TRUE;
    }
  }

  /**
   * Run a Drush command with arguments.
   *
   * @code
   * Given I run drush "pm:list" "--status=enabled"
   * @endcode
   */
  #[Given('I run drush :command :arguments')]
  public function assertDrushCommandWithArgument(string $command, string $arguments): void {
    $this->drushOutput = $this->getDriver('drush')->$command($this->fixStepArgument($arguments));
    if ($this->drushOutput === NULL) {
      $this->drushOutput = TRUE;
    }
  }

  /**
   * Assert Drush output contains a string.
   *
   * @code
   * Then drush output should contain "Drupal version"
   * @endcode
   */
  #[Then('drush output should contain :output')]
  public function assertDrushOutput(string $output): void {
    if (!str_contains((string) $this->readDrushOutput(), $this->fixStepArgument($output))) {
      throw new \Exception(sprintf("The last drush command output did not contain '%s'.\nInstead, it was:\n\n%s'", $output, $this->drushOutput));
    }
  }

  /**
   * Assert Drush output matches a regular expression.
   *
   * @code
   * Then drush output should match "/Drupal [0-9]+/"
   * @endcode
   */
  #[Then('drush output should match :regex')]
  public function assertDrushOutputMatches(string $regex): void {
    if (!preg_match($regex, (string) $this->readDrushOutput())) {
      throw new \Exception(sprintf("The pattern %s was not found anywhere in the drush output.\nOutput:\n\n%s", $regex, $this->drushOutput));
    }
  }

  /**
   * Assert Drush output does not contain a string.
   *
   * @code
   * Then drush output should not contain "error"
   * @endcode
   */
  #[Then('drush output should not contain :output')]
  public function drushOutputShouldNotContain(string $output): void {
    if (str_contains((string) $this->readDrushOutput(), $this->fixStepArgument($output))) {
      throw new \Exception(sprintf("The last drush command output did contain '%s' although it should not.\nOutput:\n\n%s'", $output, $this->drushOutput));
    }
  }

  /**
   * Print the last Drush output.
   *
   * @code
   * Then print last drush output
   * @endcode
   */
  #[Then('print last drush output')]
  public function printLastDrushOutput(): void {
    echo $this->readDrushOutput();
  }

  /**
   * Returns fixed step argument (with \\" replaced back to ").
   */
  protected function fixStepArgument(string $argument): string {
    return str_replace('\\"', '"', $argument);
  }

}
