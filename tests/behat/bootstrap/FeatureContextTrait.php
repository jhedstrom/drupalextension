<?php

/**
 * @file
 * Feature context trait for testing Drupal Extension.
 *
 * This is a test for the test framework itself. Consumer project should not
 * use any steps or functions from this file.
 *
 * @phpcs:disable PSR1.Classes.ClassDeclaration.MissingNamespace
 */

declare(strict_types=1);

use Behat\Behat\Hook\Scope\AfterFeatureScope;
use Behat\Behat\Hook\Scope\BeforeScenarioScope;
use Behat\Hook\AfterFeature;
use Behat\Hook\BeforeScenario;
use Drupal\Core\Database\Database;

/**
 * Defines application features from the specific context.
 */
trait FeatureContextTrait
{

    /**
     * Stop Mink sessions before scenarios that will spawn sub-processes.
     *
     * When a @javascript scenario runs in the parent process, Mink keeps the
     * Selenium2/Chrome connection open (via resetSessions()). This causes
     * child processes to hang when they try to establish their own connection.
     * This hook ensures all sessions are properly stopped before sub-process
     * scenarios run.
     */
    #[BeforeScenario]
    public function testStopSessionsBeforeSubProcess(BeforeScenarioScope $scope): void
    {
        $hasTraitTag = (bool) array_filter($scope->getScenario()->getTags(), fn(string $tag): bool => str_starts_with($tag, 'javascript'));

        // Stop all Mink sessions before sub-process scenarios to prevent
        // connection interference between parent and child processes.
        // @see \Behat\MinkExtension\Listener\SessionsListener::prepareDefaultMinkSession().
        if ($hasTraitTag) {
            $this->getMink()->stopSessions();
        }
    }

    /**
     * Sleep for the given number of seconds.
     *
     * @code
     * When sleep for 5 seconds
     * @endcode
     *
     * @When sleep for :seconds second(s)
     */
    public function testSleepForSeconds(int|string $seconds): void
    {
        sleep((int) $seconds);
    }

    /**
     * Clean watchdog after feature with an error.
     */
    #[AfterFeature('@errorcleanup')]
    public static function testClearWatchdog(AfterFeatureScope $scope): void
    {
        $database = Database::getConnection();
        if ($database->schema()->tableExists('watchdog')) {
            $database->truncate('watchdog')->execute();
        }
    }

    /**
     * Clear watchdog table.
     *
     * @Given the watchdog is cleared
     */
    public function testClearWatchdogTable(): void
    {
        $database = Database::getConnection();
        if ($database->schema()->tableExists('watchdog')) {
            $database->truncate('watchdog')->execute();
        }
    }
}
