<?php

/**
 * @file
 * Trait to test Behat script by using Behat cli.
 *
 * phpcs:disable Drupal.Commenting.DocComment.MissingShort
 * phpcs:disable PSR1.Classes.ClassDeclaration.MissingNamespace
 */

declare(strict_types=1);

use Behat\Behat\Hook\Scope\BeforeScenarioScope;
use Behat\Gherkin\Node\PyStringNode;
use Behat\Hook\BeforeScenario;
use Behat\Hook\BeforeStep;

/**
 * Trait BehatCliTrait.
 *
 * Additional shortcut steps for BehatCliContext.
 */
trait BehatCliTrait
{

    #[BeforeScenario]
    public function behatCliBeforeScenario(BeforeScenarioScope $scope): void
    {
        $this->behatCliCopyFixtures();
        $this->behatCliWriteFeatureContextFile();
    }

    #[BeforeStep]
    public function behatCliBeforeStep(): void
    {
        // Drupal Extension >= ^5 is coupled with Drupal core's DrupalTestBrowser.
        // This requires Drupal root to be discoverable when running Behat from a
        // random directory using Drupal Finder.
        //
        // Set environment variables for Drupal Finder.
        // This requires Drupal Finder version > 1.2 at commit:
        // @see https://github.com/webflo/drupal-finder/commit/2663b117878f4a45ca56df028460350c977f92c0
        $this->iSetEnvironmentVariable('DRUPAL_FINDER_DRUPAL_ROOT', '/var/www/html/build/web');
        $this->iSetEnvironmentVariable('DRUPAL_FINDER_COMPOSER_ROOT', '/var/www/html/build');
        $this->iSetEnvironmentVariable('DRUPAL_FINDER_VENDOR_DIR', '/var/www/html/build/vendor');
    }

    /**
     * Copy FeatureContext.php file to the working directory.
     *
     * @return string
     *   Path to written file.
     */
    public function behatCliWriteFeatureContextFile(): string
    {
        $source = __DIR__ . '/FeatureContext.php';
        $content = file_get_contents($source);
        if ($content === false) {
            throw new \RuntimeException(sprintf('Unable to access file "%s"', $source));
        }

        // Subprocess FeatureContext must extend DrupalContext (which provides
        // step definitions like "Given I am logged in as a user with the
        // :role role") instead of RawDrupalContext (which does not).
        // We cannot add DrupalContext to the subprocess behat.yml contexts
        // list because some tests (e.g., behatcli.feature) write their own
        // FeatureContext extending DrupalContext, which would cause duplicate
        // step definition errors.
        $content = str_replace(
            'use Drupal\DrupalExtension\Context\RawDrupalContext;',
            'use Drupal\DrupalExtension\Context\DrupalContext;',
            $content
        );
        $content = str_replace(
            'class FeatureContext extends RawDrupalContext',
            'class FeatureContext extends DrupalContext',
            $content
        );

        $filename = 'features/bootstrap/FeatureContext.php';
        $this->createFileInWorkingDir($filename, $content);

        if (static::behatCliIsDebug()) {
            static::behatCliPrintFileContents($filename, 'FeatureContext.php');
        }

        return $filename;
    }

    /**
     * @Given /^scenario steps(?: tagged with "([^"]*)")?:$/
     */
    public function behatCliWriteScenarioSteps(PyStringNode $content, $tags = ''): void
    {
        $content = strtr((string) $content, ["'''" => '"""']);

        // Make sure that indentation in provided content is accurate.
        $contentLines = explode(PHP_EOL, $content);
        foreach ($contentLines as $k => $contentLine) {
            $contentLines[$k] = str_repeat(' ', 4) . trim($contentLine);
        }
        $content = implode(PHP_EOL, $contentLines);

        $tokens = [
            '{{SCENARIO_CONTENT}}' => $content,
            '{{ADDITIONAL_TAGS}}' => $tags,
        ];

        $content = <<<'EOL'
Feature: Stub feature';
  @api {{ADDITIONAL_TAGS}}
  Scenario: Stub scenario title
{{SCENARIO_CONTENT}}
EOL;

        $content = strtr($content, $tokens);
        $content = preg_replace('/\{\{[^\}]+\}\}/', '', $content);

        $filename = 'features/stub.feature';
        $this->createFileInWorkingDir($filename, $content);

        if (static::behatCliIsDebug()) {
            static::behatCliPrintFileContents($filename, 'Feature Stub');
        }
    }

    /**
     * @Given some behat configuration
     */
    public function behatCliWriteBehatYml(): void
    {
        $content = <<<'EOL'
default:
  suites:
    default:
      contexts:
        - FeatureContext
        - Drupal\DrupalExtension\Context\ConfigContext
        - Drupal\DrupalExtension\Context\DrushContext
        - Drupal\DrupalExtension\Context\MailContext
        - Drupal\DrupalExtension\Context\MarkupContext
        - Drupal\DrupalExtension\Context\MessageContext
        - Drupal\DrupalExtension\Context\MinkContext
        - Drupal\DrupalExtension\Context\RandomContext
        - DrevOps\BehatScreenshotExtension\Context\ScreenshotContext
  extensions:
    Drupal\MinkExtension:
      browserkit_http: ~
      base_url: http://drupal
      browser_name: chrome
      javascript_session: selenium2
      selenium2:
        wd_host: "http://chrome:4444/wd/hub"
        capabilities:
          browser: chrome
          extra_capabilities:
            "goog:chromeOptions":
              args:
                - '--disable-gpu'            # Disables hardware acceleration required in containers and cloud-based instances (like CI runners) where GPU is not available.
                - '--disable-extensions'     # Disables all installed Chrome extensions. Useful in testing environments to avoid interference from extensions.
                - '--disable-infobars'       # Hides the infobar that Chrome displays for various notifications, like warnings when opening multiple tabs.
                - '--disable-popup-blocking' # Disables the popup blocker, allowing all popups to appear. Useful in testing scenarios where popups are expected.
                - '--disable-translate'      # Disables the built-in translation feature, preventing Chrome from offering to translate pages.
                - '--no-first-run'           # Skips the initial setup screen that Chrome typically shows when running for the first time.
                - '--test-type'              # Disables certain security features and UI components that are unnecessary for automated testing, making Chrome more suitable for test environments.
    Drupal\DrupalExtension:
      api_driver: drupal
      drupal:
        drupal_root: /var/www/html/build/web
      drush:
        root: /var/www/html/build/web
      region_map:
        main content: "#main"
      selectors:
        message_selector: '.messages'
        error_message_selector: '.messages--error'
        success_message_selector: '.messages--status'
        warning_message_selector: '.messages--warning'
    DrevOps\BehatScreenshotExtension:
      dir: '%paths.base%/.logs/screenshots'
      purge: false
      on_failed: true
      always_fullscreen: true
      fullscreen_algorithm: resize
      info_types:
        - url
        - feature
        - step
        - datetime
EOL;

        if (static::behatCliIsCoverageEnabled()) {
            // Generate unique coverage filename for this subprocess to avoid conflicts.
            $coverageId = md5($this->workingDir);
            $coverageContent = <<<EOL

    DVDoug\Behat\CodeCoverage\Extension:
      filter:
        include:
          directories:
            /var/www/html/src: ~
      reports:
        text:
          showColors: true
          showOnlySummary: true
        php:
          target: /var/www/html/.logs/coverage/behat_cli/phpcov/{$coverageId}.php
EOL;
            $content .= $coverageContent;
        }

        $filename = 'behat.yml';
        $this->createFileInWorkingDir($filename, $content);

        if (static::behatCliIsDebug()) {
            static::behatCliPrintFileContents($filename, 'Behat Config');
        }
    }

    /**
     * @Then it should fail with an error:
     */
    public function behatCliAssertFailWithError(PyStringNode $message): void
    {
        $this->itShouldPassOrFailWith('fail', $message);
        // Enforce assertion exceptions (ExpectationException, ElementNotFoundException, or generic Exception).
        // Non-assertion exceptions should be thrown as \RuntimeException.
        $output = $this->getOutput();
        $hasValidException = str_contains((string) $output, ' (Exception)')
        || str_contains((string) $output, ' (Behat\Mink\Exception\ExpectationException)')
        || str_contains((string) $output, ' (Behat\Mink\Exception\ElementNotFoundException)');
        if (!$hasValidException) {
            throw new \RuntimeException('The output does not contain an assertion exception string as expected.');
        }
        if (str_contains((string) $output, ' (RuntimeException)')) {
            throw new \RuntimeException('The output contains "(RuntimeException)" string but it should not.');
        }
    }

    /**
     * @Then it should fail with an exception:
     */
    public function behatCliAssertFailWithException(PyStringNode $message): void
    {
        $this->itShouldPassOrFailWith('fail', $message);
        // Enforce \RuntimeException for all non-assertion exceptions. Assertion
        // exceptions should be thrown as \Exception.
        if (!str_contains($this->getOutput(), ' (RuntimeException)')) {
            throw new \RuntimeException('The output does not contain an "(RuntimeException)" string as expected.');
        }
        if (str_contains($this->getOutput(), ' (Exception)')) {
            throw new \RuntimeException('The output contains "(Exception)" string but it should not.');
        }
    }

    /**
     * @Then it should fail with a :exception exception:
     */
    public function behatCliAssertFailWithCustomException(string $exception, PyStringNode $message): void
    {
        $this->itShouldPassOrFailWith('fail', $message);
        // Enforce \RuntimeException for all non-assertion exceptions. Assertion
        // exceptions should be thrown as \Exception.
        if (!str_contains($this->getOutput(), ' (' . $exception . ')')) {
            throw new \RuntimeException(sprintf('The output does not contain an "(%s)" string as expected.', $exception));
        }
    }

    /**
     * Checks whether last command output does not contain provided string.
     *
     * @param \Behat\Gherkin\Node\PyStringNode $text
     *   PyString text instance.
     *
     * @Then the output should not contain:
     */
    public function theOutputShouldNotContain(PyStringNode $text): void
    {
        if (str_contains($this->getOutput(), $this->getExpectedOutput($text))) {
            throw new \RuntimeException(sprintf('Output contains "%s" but should not.', $this->getExpectedOutput($text)));
        }
    }

    /**
     * Helper to print file comments.
     */
    protected static function behatCliPrintFileContents(string $filename, string $title = '')
    {
        if (!is_readable($filename)) {
            throw new \RuntimeException(sprintf('Unable to access file "%s"', $filename));
        }

        $content = file_get_contents($filename);

        print sprintf('-------------------- %s START --------------------', $title) . PHP_EOL;
        print $filename . PHP_EOL;
        print_r($content);
        print PHP_EOL;
        print sprintf('-------------------- %s FINISH --------------------', $title) . PHP_EOL;
    }

    /**
     * Helper to check if debug mode is enabled.
     */
    protected static function behatCliIsDebug(): bool
    {
        // Change to TRUE to see debug messages for this trait.
        return false;
    }

    /**
     * Helper to check if code coverage is enabled.
     */
    protected static function behatCliIsCoverageEnabled(): bool
    {
        return ini_get('pcov.enabled') === '1' && !empty(ini_get('pcov.directory'));
    }

    /**
     * Copy fixtures to the working directory.
     */
    protected function behatCliCopyFixtures()
    {
        // Copy fixtures to the working directory.
        $fixturePath = 'tests/behat/fixtures';
        // @note Hardcoded path to the fixture directory.
        $fixturePathAbs = '/app' . DIRECTORY_SEPARATOR . $fixturePath;
        if (is_dir($fixturePathAbs)) {
            $dst = $this->workingDir . DIRECTORY_SEPARATOR . $fixturePath;
            mkdir($dst, 0777, true);
            // Copy fixtures from the webroot to the working directory.
            foreach (glob($fixturePathAbs . '/*') as $file) {
                // @note Only copy files for speed.
                if (is_file($file)) {
                    $filename = basename($file);
                    copy($file, $dst . DIRECTORY_SEPARATOR . $filename);
                }
            }
        }
    }
}
