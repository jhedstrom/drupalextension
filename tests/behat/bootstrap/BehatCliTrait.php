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

        $traits = [];

        // Scan scenario tags and extract trait names from tags starting with
        // 'trait:'. For example, @trait:PathTrait or @trait:D7\\UserTrait.
        foreach ($scope->getScenario()->getTags() as $tag) {
            if (str_starts_with($tag, 'trait:')) {
                $tags = trim(substr($tag, strlen('trait:')));
                $tags = explode(',', $tags);
                $tags = array_map(fn(string $value): string => trim(str_replace('\\\\', '\\', $value)), $tags);
                $traits = array_merge($traits, $tags);
                break;
            }
        }

        $traits = array_filter($traits);
        $traits = array_unique($traits);

        // Only create FeatureContext.php if there is at least one '@trait:' tag.
        if (empty($traits)) {
            return;
        }

        $this->behatCliWriteFeatureContextFile($traits);
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
     * Create FeatureContext.php file.
     *
     * @param array $traits
     *   Optional array of trait classes.
     *
     * @return string
     *   Path to written file.
     */
    public function behatCliWriteFeatureContextFile(array $traits = []): string
    {
        $tokens = [
            '{{USE_DECLARATION}}' => '',
            '{{USE_IN_CLASS}}' => '',
        ];
        foreach ($traits as $trait) {
            // Check if trait contains slash to determine if it's in a subdirectory.
            $traitParts = explode('\\', (string) $trait);
            $traitName = end($traitParts);
            $traitNamespace = implode('\\', array_slice($traitParts, 0, -1));

            // Check if the trait is in a subdirectory (indicated by namespace parts)
            if (!empty($traitNamespace)) {
                // The trait name already includes namespace.
                $traitClass = '\\Drupal\\DrupalExtension\\' . $trait;
                $tokens['{{USE_DECLARATION}}'] .= sprintf('use Drupal\\DrupalExtension\\%s;' . PHP_EOL, $trait);
            } else {
                // First try to find the trait in the base namespace.
                $traitClass = '\\Drupal\\DrupalExtension\\' . $trait;
                $contextDir = null;

                // Check if trait exists in the base namespace.
                if (class_exists($traitClass)) {
                    // Get the file path to determine if it's in a subdirectory.
                    $reflection = new \ReflectionClass($traitClass);
                    $filePath = $reflection->getFileName();

                    if ($filePath) {
                        // Found in the base namespace.
                        $tokens['{{USE_DECLARATION}}'] .= sprintf('use Drupal\\DrupalExtension\\%s;' . PHP_EOL, $trait);
                    }
                } else {
                    // Not found in base namespace, let's check subdirectories
                    // Get a list of directories under src/.
                    $baseDir = dirname(__DIR__, 3) . DIRECTORY_SEPARATOR . 'src';
                    $dirs = array_filter(glob($baseDir . DIRECTORY_SEPARATOR . '*'), is_dir(...));

                    // Convert directory names to potential namespace parts.
                    foreach ($dirs as $dir) {
                        $contextDir = basename($dir);
                        $contextTraitClass = sprintf('\\Drupal\\DrupalExtension\\%s\\%s', $contextDir, $trait);

                        if (class_exists($contextTraitClass)) {
                              // Found in a subdirectory.
                              $traitClass = $contextTraitClass;
                              $tokens['{{USE_DECLARATION}}'] .= sprintf('use Drupal\\DrupalExtension\\%s\\%s;' . PHP_EOL, $contextDir, $trait);
                              break;
                        }
                    }

                    // If not found in any subdirectory, default to base namespace.
                    if (!class_exists($traitClass)) {
                        $tokens['{{USE_DECLARATION}}'] .= sprintf('use Drupal\\DrupalExtension\\%s;' . PHP_EOL, $trait);
                    }
                }
            }
            $traitNameParts = explode('\\', (string) $trait);
            $traitName = end($traitNameParts);
            $tokens['{{USE_IN_CLASS}}'] .= sprintf('use %s;' . PHP_EOL, $traitName);
        }

        $content = <<<'EOL'
<?php

use Drupal\DrupalExtension\Context\DrupalContext;
{{USE_DECLARATION}}

class FeatureContext extends DrupalContext {
  {{USE_IN_CLASS}}

  use FeatureContextTrait;

  /**
   * @Given I throw test exception with message :message
   */
  public function throwTestException($message) {
    throw new \RuntimeException($message);
  }

  /**
   * @Given set Drupal7 watchdog error level :level
   * @Given set Drupal7 watchdog error level :level of type :type
   */
  public function setWatchdogErrorDrupal7($level, $type = 'php') {
    watchdog($type, 'test', [], $level);
  }

  /**
   * @Given set watchdog error level :level
   * @Given set watchdog error level :level of type :type
   */
  public function testSetWatchdogError($level, $type = 'php') {
    \Drupal::logger($type)->log($level, 'test');
  }

}
EOL;

        $content = strtr($content, $tokens);
        $content = preg_replace('/\{\{[^\}]+\}\}/', '', $content);

        $filename = 'features/bootstrap/FeatureContext.php';
        $this->createFileInWorkingDir($filename, $content);

        $featureContextTraitContent = file_get_contents(__DIR__ . '/FeatureContextTrait.php');
        if ($featureContextTraitContent === false) {
            throw new \RuntimeException(sprintf('Unable to access file "%s"', __DIR__ . '/FeatureContextTrait.php'));
        }
        $featureContextTrait = 'features/bootstrap/FeatureContextTrait.php';
        $this->createFileInWorkingDir($featureContextTrait, $featureContextTraitContent);

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
        - Drupal\DrupalExtension\Context\MinkContext
        - DrevOps\BehatScreenshotExtension\Context\ScreenshotContext
  extensions:
    Drupal\MinkExtension:
      browserkit_http: ~
      base_url: http://proxy
    Drupal\DrupalExtension:
      api_driver: drupal
      drupal:
        drupal_root: /var/www/html/build/web
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
            /app/src: ~
      reports:
        text:
          showColors: true
          showOnlySummary: true
        php:
          target: /app/.logs/coverage/behat_cli/phpcov/{$coverageId}.php
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
