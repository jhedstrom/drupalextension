<?php

/**
 * @file
 * Trait to test Behat script by using Behat cli.
 *
 * phpcs:disable Drupal.Commenting.DocComment.MissingShort
 * phpcs:disable PSR1.Classes.ClassDeclaration.MissingNamespace
 */

declare(strict_types=1);

use Symfony\Component\Yaml\Yaml;
use DVDoug\Behat\CodeCoverage\Extension;
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
     * We re-use tests/behat/bootstrap/FeatureContext.php in Behat CLI
     * subprocesses to provide access to the same step definitions.
     *
     * @return string
     *   Path to written file.
     */
    public function behatCliWriteFeatureContextFile(): string
    {
        $source = __DIR__ . '/FeatureContext.php';
        // Relative destination path in the destination working directory.
        $dst = 'features/bootstrap/FeatureContext.php';

        $content = file_get_contents($source);
        if ($content === false) {
            throw new \RuntimeException(sprintf('Unable to access file "%s"', $source));
        }

        $this->createFileInWorkingDir($dst, $content);

        if (static::behatCliIsDebug()) {
            static::behatCliPrintFileContents($this->workingDir . DIRECTORY_SEPARATOR. $dst, 'FeatureContext.php');
        }

        return $dst;
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
  {{ADDITIONAL_TAGS}}
  Scenario: Stub scenario title
{{SCENARIO_CONTENT}}
EOL;

        $content = strtr($content, $tokens);
        $content = preg_replace('/\{\{[^\}]+\}\}/', '', $content);

        $filename = 'features/stub.feature';
        $this->createFileInWorkingDir($filename, $content);

        if (static::behatCliIsDebug()) {
            static::behatCliPrintFileContents($this->workingDir . DIRECTORY_SEPARATOR. $filename, 'Feature Stub');
        }
    }

    /**
     * @Given some behat configuration
     */
    public function behatCliWriteBehatYml(): void
    {
        // @note Hardcoded path to the project root.
        $source = '/var/www/html/behat.yml';

        $content = file_get_contents($source);
        if ($content === false) {
            throw new \RuntimeException(sprintf('Unable to access file "%s"', $source));
        }

        $yaml = Yaml::parse($content);

        // Remove autoload and paths configuration to avoid conflicts with
        // the default Behat configuration in the working directory.
        unset($yaml['default']['autoload']);

        // Remove paths configuration to avoid conflicts with the default Behat
        // configuration in the working directory.
        unset($yaml['default']['suites']['default']['paths']);

        // Find the BehatCliContext and remove it from the contexts list.
        $index= array_search('BehatCliContext', $yaml['default']['suites']['default']['contexts'], true);
        if ($index !== false) {
            unset($yaml['default']['suites']['default']['contexts'][$index]);
        }

        // Find the BehatCliContext and remove it from the contexts list.
        $index= array_search('BehatCliContext', $yaml['drupal']['suites']['default']['contexts'], true);
        if ($index !== false) {
            unset($yaml['drupal']['suites']['default']['contexts'][$index]);
        }

        if (static::behatCliIsCoverageEnabled()) {
            // Update the code coverage configuration to use an alternative
            // coverage report target. This report is merged into a single report
            // with scripts/merge-coverage.php.
            $coverageId = md5($this->workingDir);
            $yaml['default']['extensions'][Extension::class] = [
                'filter' => [
                    'include' => [
                        'directories' => [
                            '/var/www/html/src' => null,
                        ],
                    ],
                ],
                'reports' => [
                    'text' => [
                        'showColors' => true,
                        'showOnlySummary' => true,
                    ],
                    'php' => [
                        'target' => '/var/www/html/.logs/coverage/behat_cli/phpcov/' . $coverageId . '.php',
                    ],
                ],
            ];
        } else {
            // Remove code coverage configuration if we are not running
            // with code coverage.
            unset($yaml['default']['extensions'][Extension::class]);
        }

        $content= Yaml::dump($yaml, 4, 2);

        $filename = 'behat.yml';
        $this->createFileInWorkingDir($filename, $content);

        if (static::behatCliIsDebug()) {
            static::behatCliPrintFileContents($this->workingDir . DIRECTORY_SEPARATOR . $filename, 'Behat Config (copied)');
        }
    }

    /**
     * @Then it should fail with an error:
     */
    public function behatCliAssertFailWithError(PyStringNode $message): void
    {
        $this->itShouldPassOrFailWith('fail', $message);

        $output = $this->getOutput();

        $hasValidException = str_contains((string) $output, ' (Exception)') || str_contains((string) $output, ' (Behat\Mink\Exception');

        if (!$hasValidException) {
            throw new \RuntimeException('The output does not contain an assertion exception string as expected.');
        }

        // RuntimeException is used for unexpected exceptions, not assertions.
        if (str_contains((string) $output, 'RuntimeException')) {
            throw new \RuntimeException('The output contains "RuntimeException" string but it should not.');
        }
    }

    /**
     * @When I run behat
     */
    public function behatCliRun(string $profile = 'default'):void
    {
         $this->behatCliRunWithProfile($profile);
    }

    /**
     * @When I run behat with :profile profile
     */
    public function behatCliRunWithProfile(string $profile):void
    {
        $this->iRunBehat('--profile=' . $profile . ' --no-colors');
    }

    /**
     * @Then it should fail with an exception:
     */
    public function behatCliAssertFailWithException(PyStringNode $message): void
    {
        $this->behatCliAssertFailWithCustomException('RuntimeException', $message);

        if (str_contains($this->getOutput(), 'Exception') && !str_contains($this->getOutput(), 'RuntimeException')) {
            throw new \RuntimeException('The output contains "Exception" string but it should not.');
        }
    }

    /**
     * @Then it should fail with a :exception exception:
     */
    public function behatCliAssertFailWithCustomException(string $exception, PyStringNode $message): void
    {
        $this->itShouldPassOrFailWith('fail', $message);

        $actual = $this->getOutput();
        $expected = ' (' . $exception . ')';

        // Enforce \RuntimeException for all non-assertion exceptions. Assertion
        // exceptions should be thrown as \Exception or specific exception class
        // if provided.
        if (!str_contains((string) $actual, $expected)) {
            throw new \RuntimeException(sprintf('The output does not contain an "%s" string as expected: %s', $expected, $actual));
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
    public function behatCliAssertOutputNotContains(PyStringNode $text): void
    {
        $expected = $this->getExpectedOutput($text);
        $actual = $this->getOutput();

        if (str_contains((string) $actual, (string) $expected)) {
            throw new \RuntimeException(sprintf('Output contains "%s" but should not: %s', $expected, $actual));
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

        $header = sprintf('-------------------- %s START --------------------', $title);
        fwrite(STDERR, PHP_EOL);
        fwrite(STDERR, $header . PHP_EOL);
        fwrite(STDERR, $filename . PHP_EOL);
        fwrite(STDERR, str_repeat('-', strlen($header)) . PHP_EOL);
        fwrite(STDERR, PHP_EOL);
        fwrite(STDERR, PHP_EOL);
        fwrite(STDERR, $content);
        fwrite(STDERR, PHP_EOL);
        fwrite(STDERR, sprintf('-------------------- %s FINISH --------------------', $title) . PHP_EOL);
        fwrite(STDERR, PHP_EOL);
    }

    /**
     * Helper to check if debug mode is enabled.
     */
    protected static function behatCliIsDebug(): bool
    {
        return (bool) getenv('BEHAT_CLI_DEBUG');
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
        $fixturePathRel = 'tests/behat/fixtures';

        // @note Hardcoded path to the fixture directory.
        $fixturePathAbs = '/var/www/html/' . DIRECTORY_SEPARATOR . $fixturePathRel;

        if (is_dir($fixturePathAbs)) {
            $dst = $this->workingDir . DIRECTORY_SEPARATOR . $fixturePathRel;
            mkdir($dst, 0777, true);

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
