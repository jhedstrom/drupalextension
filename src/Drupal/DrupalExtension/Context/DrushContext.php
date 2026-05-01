<?php

declare(strict_types=1);

namespace Drupal\DrupalExtension\Context;

use Behat\Mink\Exception\ExpectationException;
use Behat\Step\Given;
use Behat\Step\Then;
use Behat\Step\When;
use Behat\Behat\Context\TranslatableContext;

/**
 * Provides step definitions for interacting directly with Drush commands.
 */
class DrushContext extends RawDrupalContext implements TranslatableContext {

  /**
   * Keep track of drush output.
   */
  // phpcs:ignore DrevOps.NamingConventions.LocalVariableNaming.NotSnakeCase
  protected string|bool|null $drushOutput = NULL;

  /**
   * {@inheritdoc}
   */
  public static function getTranslationResources() {
    return self::getDrupalTranslationResources();
  }

  /**
   * Return the most recent drush command output.
   *
   * @return string|bool
   *   The most recent drush command output.
   */
  public function readDrushOutput(): string|bool {
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
  public function iRunDrush(string $command): void {
    if (!$this->drushOutput = $this->getDriver('drush')->$command()) {
      $this->drushOutput = TRUE;
    }
  }

  /**
   * Run a Drush command with arguments.
   *
   * The arguments string is appended to the command verbatim, so it may
   * contain Drush options (flags) as well as positional arguments.
   *
   * @code
   * Given I run drush "pm:list" "--status=enabled"
   * Given I run drush "config:get" "system.site uuid --format=string"
   * @endcode
   */
  #[Given('I run drush :command :arguments')]
  public function iRunDrushWithArguments(string $command, string $arguments): void {
    $this->drushOutput = $this->getDriver('drush')->$command($this->fixStepArgument($arguments));
    if ($this->drushOutput === NULL) {
      $this->drushOutput = TRUE;
    }
  }

  /**
   * Assert the Drush output contains a string.
   *
   * @code
   * Then the drush output should contain "Drupal version"
   * @endcode
   */
  #[Then('the drush output should contain :output')]
  public function drushOutputAssertContains(string $output): void {
    if (!str_contains((string) $this->readDrushOutput(), $this->fixStepArgument($output))) {
      throw new ExpectationException(sprintf("The last drush command output did not contain '%s'.\nInstead, it was:\n\n%s'", $output, $this->drushOutput), $this->getSession()->getDriver());
    }
  }

  /**
   * Assert the Drush output matches a regular expression.
   *
   * @code
   * Then the drush output should match "/Drupal [0-9]+/"
   * @endcode
   */
  #[Then('the drush output should match :regex')]
  public function drushOutputAssertMatches(string $regex): void {
    if (!preg_match($regex, (string) $this->readDrushOutput())) {
      throw new ExpectationException(sprintf("The pattern %s was not found anywhere in the drush output.\nOutput:\n\n%s", $regex, $this->drushOutput), $this->getSession()->getDriver());
    }
  }

  /**
   * Assert the Drush output does not contain a string.
   *
   * @code
   * Then the drush output should not contain "error"
   * @endcode
   */
  #[Then('the drush output should not contain :output')]
  public function drushOutputAssertNotContains(string $output): void {
    if (str_contains((string) $this->readDrushOutput(), $this->fixStepArgument($output))) {
      throw new ExpectationException(sprintf("The last drush command output did contain '%s' although it should not.\nOutput:\n\n%s", $output, $this->drushOutput), $this->getSession()->getDriver());
    }
  }

  /**
   * Print the last Drush output.
   *
   * @code
   * When I print the last drush output
   * @endcode
   */
  #[When('I print the last drush output')]
  public function iPrintLastDrushOutput(): void {
    echo $this->readDrushOutput();
  }

  /**
   * Returns fixed step argument (with \\" replaced back to ").
   */
  protected function fixStepArgument(string $argument): string {
    return str_replace('\\"', '"', $argument);
  }

}
