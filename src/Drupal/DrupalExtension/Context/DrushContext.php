<?php

declare(strict_types=1);

namespace Drupal\DrupalExtension\Context;

use Behat\Mink\Exception\ExpectationException;
use Behat\Step\Given;
use Behat\Step\Then;
use Behat\Step\When;
use Behat\Behat\Context\TranslatableContext;
use Drupal\Driver\DrushDriver;

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
   * Run a Drush command that is expected to fail.
   *
   * The command runs without aborting the step on a non-zero exit, capturing
   * its error output so it can be asserted with the "the drush output should"
   * steps. The step fails if the command instead succeeds.
   *
   * @code
   * Given I run the failing drush command "pm:uninstall no_such_module"
   * @endcode
   */
  #[Given('I run the failing drush command :command')]
  public function iRunFailingDrush(string $command): void {
    $this->runFailingDrush($command);
  }

  /**
   * Run a Drush command with arguments that is expected to fail.
   *
   * The arguments string is appended to the command verbatim, so it may
   * contain Drush options (flags) as well as positional arguments.
   *
   * @code
   * Given I run the failing drush command "pm:uninstall" "no_such_module"
   * @endcode
   */
  #[Given('I run the failing drush command :command :arguments')]
  public function iRunFailingDrushWithArguments(string $command, string $arguments): void {
    $this->runFailingDrush($command, $arguments);
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
   * Run a Drush command expecting it to fail, capturing its output.
   *
   * @param string $command
   *   The Drush command to run.
   * @param string|null $arguments
   *   Optional arguments string appended to the command verbatim.
   *
   * @throws \Behat\Mink\Exception\ExpectationException
   *   When the command exits with a zero (success) status.
   */
  protected function runFailingDrush(string $command, ?string $arguments = NULL): void {
    $driver = $this->getDriver('drush');

    if (!$driver instanceof DrushDriver) {
      throw new \RuntimeException(sprintf('The active Drupal driver "%s" does not support capturing drush command results.', $driver::class));
    }

    $args = $arguments === NULL ? [] : [$this->fixStepArgument($arguments)];
    $result = $driver->drushResult($command, $args);

    // Prefer stdout, falling back to stderr, to match the success-path output.
    $output = ($result->output === '' || $result->output === '0') ? $result->errorOutput : $result->output;
    $this->drushOutput = $output;

    if ($result->exitCode === 0) {
      throw new ExpectationException(sprintf("Expected the drush command '%s' to fail, but it exited 0.\nOutput:\n\n%s", $command, $output), $this->getSession()->getDriver());
    }
  }

  /**
   * Returns fixed step argument (with \\" replaced back to ").
   */
  protected function fixStepArgument(string $argument): string {
    return str_replace('\\"', '"', $argument);
  }

}
