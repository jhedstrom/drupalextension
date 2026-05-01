<?php

/**
 * @file
 * Trait to test Behat script by using Behat cli.
 *
 * phpcs:disable Drupal.Commenting.DocComment.MissingShort
 * phpcs:disable PSR1.Classes.ClassDeclaration.MissingNamespace
 */

declare(strict_types=1);

use Behat\Step\Given;
use Behat\Step\Then;
use Behat\Step\When;
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
trait BehatCliTrait {

  /**
   * Sets up Behat CLI environment before each scenario.
   */
  #[BeforeScenario]
  public function behatCliBeforeScenario(BeforeScenarioScope $scope): void {
    $this->behatCliCopyFixtures();
    $this->behatCliWriteFeatureContextFile();
  }

  /**
   * Sets the working directory for Behat CLI before each step.
   */
  #[BeforeStep]
  public function behatCliBeforeStep(): void {
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
  public function behatCliWriteFeatureContextFile(): string {
    $source = __DIR__ . '/FeatureContext.php';
    // Relative destination path in the destination working directory.
    $dst = 'features/bootstrap/FeatureContext.php';

    $content = file_get_contents($source);
    if ($content === FALSE) {
      throw new \RuntimeException(sprintf('Unable to access file "%s"', $source));
    }

    $this->createFileInWorkingDir($dst, $content);

    if (static::behatCliIsDebug()) {
      static::behatCliPrintFileContents($this->workingDir . DIRECTORY_SEPARATOR . $dst, 'FeatureContext.php');
    }

    return $dst;
  }

  /**
   * Writes scenario steps to a stub feature file in the working directory.
   */
  #[Given('/^scenario steps(?: tagged with "([^"]*)")?:$/')]
  public function behatCliWriteScenarioSteps(PyStringNode $content, $tags = ''): void {
    $content = strtr((string) $content, ["'''" => '"""']);

    // Make sure that indentation in provided content is accurate.
    $content_lines = explode(PHP_EOL, $content);
    foreach ($content_lines as $k => $content_line) {
      $content_lines[$k] = str_repeat(' ', 4) . trim($content_line);
    }
    $content = implode(PHP_EOL, $content_lines);

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
      static::behatCliPrintFileContents($this->workingDir . DIRECTORY_SEPARATOR . $filename, 'Feature Stub');
    }
  }

  /**
   * Writes a behat.yml configuration file to the working directory.
   */
  #[Given('some behat configuration')]
  public function behatCliWriteBehatYml(): void {
    // @note Hardcoded path to the project root.
    $source = '/var/www/html/behat.yml';

    $content = file_get_contents($source);
    if ($content === FALSE) {
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
    $index = array_search('BehatCliContext', $yaml['default']['suites']['default']['contexts'], TRUE);
    if ($index !== FALSE) {
      unset($yaml['default']['suites']['default']['contexts'][$index]);
    }

    // Find the BehatCliContext and remove it from the contexts list.
    $index = array_search('BehatCliContext', $yaml['drupal']['suites']['default']['contexts'], TRUE);
    if ($index !== FALSE) {
      unset($yaml['drupal']['suites']['default']['contexts'][$index]);
    }

    if (static::behatCliIsCoverageEnabled()) {
      // Update the code coverage configuration to use an alternative
      // coverage report target. This report is merged into a single report
      // with scripts/merge-coverage.php.
      $coverage_id = md5($this->workingDir);
      $yaml['default']['extensions'][Extension::class] = [
        'filter' => [
          'include' => [
            'directories' => [
              '/var/www/html/src' => NULL,
            ],
          ],
        ],
        'reports' => [
          'text' => [
            'showColors' => TRUE,
            'showOnlySummary' => TRUE,
          ],
          'php' => [
            'target' => '/var/www/html/.logs/coverage/behat_cli/phpcov/' . $coverage_id . '.php',
          ],
        ],
      ];
    }
    else {
      // Remove code coverage configuration if we are not running
      // with code coverage.
      unset($yaml['default']['extensions'][Extension::class]);
    }

    // Resolve the drush binary to an absolute path so subprocess tests
    // can find it regardless of their working directory.
    // The source behat.yml is at /var/www/html, so resolve relative to that.
    $project_root = dirname($source);
    $drush_binary = $project_root . '/vendor/bin/drush';
    if (file_exists($drush_binary)) {
      foreach (['default', 'drupal', 'drupal_https'] as $profile) {
        if (isset($yaml[$profile]['extensions']['Drupal\DrupalExtension']['drush'])) {
          $yaml[$profile]['extensions']['Drupal\DrupalExtension']['drush']['binary'] = $drush_binary;
        }
      }
    }

    $content = Yaml::dump($yaml, 4, 2);

    $filename = 'behat.yml';
    $this->createFileInWorkingDir($filename, $content);

    if (static::behatCliIsDebug()) {
      static::behatCliPrintFileContents($this->workingDir . DIRECTORY_SEPARATOR . $filename, 'Behat Config (copied)');
    }
  }

  /**
   * Sets 'field_parser: legacy' on the drupal profile in the subprocess config.
   *
   * Use after 'Given some behat configuration' to exercise the legacy
   * field-value parser inside a Behat subprocess invocation.
   */
  #[Given('the behat configuration uses the legacy field parser')]
  public function behatCliUseLegacyFieldParser(): void {
    $config_file = $this->workingDir . DIRECTORY_SEPARATOR . 'behat.yml';
    $yaml = Yaml::parse((string) file_get_contents($config_file));
    $yaml['drupal']['extensions']['Drupal\DrupalExtension']['field_parser'] = 'legacy';
    file_put_contents($config_file, Yaml::dump($yaml, 4, 2));
  }

  /**
   * Switches message selectors to the deprecated flat form in subprocess.
   *
   * Strips the nested 'messages:' map under
   * 'Drupal\DrupalExtension.selectors:' and rewrites the four short keys
   * ('default', 'error', 'success', 'warning') to their legacy flat
   * counterparts on the same map, exercising the backward-compat path on
   * 'MessageContext' that emits the one-shot deprecation notice.
   */
  #[Given('the behat configuration uses the deprecated message selectors')]
  public function behatCliUseDeprecatedMessageSelectors(): void {
    $config_file = $this->workingDir . DIRECTORY_SEPARATOR . 'behat.yml';
    $yaml = Yaml::parse((string) file_get_contents($config_file));

    $legacy_key_map = [
      'default' => 'message_selector',
      'error' => 'error_message_selector',
      'success' => 'success_message_selector',
      'warning' => 'warning_message_selector',
    ];

    foreach (['default', 'drupal'] as $profile) {
      $selectors = &$yaml[$profile]['extensions']['Drupal\DrupalExtension']['selectors'];

      if (!isset($selectors['messages'])) {
        unset($selectors);
        continue;
      }

      $messages = $selectors['messages'];

      foreach ($legacy_key_map as $short => $legacy) {
        if (!isset($messages[$short])) {
          continue;
        }

        $selectors[$legacy] = $messages[$short];
      }

      unset($selectors['messages']);
      unset($selectors);
    }

    file_put_contents($config_file, Yaml::dump($yaml, 4, 2));
  }

  /**
   * Asserts that behat failed with the given assertion error.
   */
  #[Then('it should fail with an error:')]
  public function behatCliAssertFailWithError(PyStringNode $message): void {
    $this->itShouldPassOrFailWith('fail', $message);

    $output = $this->getOutput();

    $has_valid_exception = str_contains((string) $output, ' (Exception)') || str_contains((string) $output, ' (Behat\Mink\Exception');

    if (!$has_valid_exception) {
      throw new \RuntimeException('The output does not contain an assertion exception string as expected.');
    }

    // RuntimeException is used for unexpected exceptions, not assertions.
    if (str_contains((string) $output, 'RuntimeException')) {
      throw new \RuntimeException('The output contains "RuntimeException" string but it should not.');
    }
  }

  /**
   * Runs behat with the default profile.
   */
  #[When('I run behat')]
  public function behatCliRun(string $profile = 'default'):void {
    $this->behatCliRunWithProfile($profile);
  }

  /**
   * Runs behat with the given profile.
   */
  #[When('I run behat with :profile profile')]
  public function behatCliRunWithProfile(string $profile):void {
    $this->iRunBehat('--profile=' . $profile . ' --no-colors');
  }

  /**
   * Runs behat with the drupal profile and a custom login_wait value.
   */
  #[When('I run behat with drupal profile and :key set to :value')]
  public function behatCliRunWithDrupalProfileAndConfig(string $key, string $value):void {
    $config_file = $this->workingDir . DIRECTORY_SEPARATOR . 'behat.yml';
    $yaml = Yaml::parse(file_get_contents($config_file));
    $yaml['drupal']['extensions']['Drupal\DrupalExtension'][$key] = is_numeric($value) ? (int) $value : $value;
    file_put_contents($config_file, Yaml::dump($yaml, 4, 2));
    $this->iRunBehat('--profile=drupal --no-colors');
  }

  /**
   * Asserts that behat failed with the given RuntimeException.
   */
  #[Then('it should fail with an exception:')]
  public function behatCliAssertFailWithException(PyStringNode $message): void {
    $this->behatCliAssertFailWithCustomException('RuntimeException', $message);

    if (str_contains($this->getOutput(), 'Exception') && !str_contains($this->getOutput(), 'RuntimeException')) {
      throw new \RuntimeException('The output contains "Exception" string but it should not.');
    }
  }

  /**
   * Asserts that behat failed with the given exception class.
   */
  #[Then('it should fail with a :exception exception:')]
  public function behatCliAssertFailWithCustomException(string $exception, PyStringNode $message): void {
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
   */
  #[Then('the output should not contain:')]
  public function behatCliAssertOutputNotContains(PyStringNode $text): void {
    $expected = $this->getExpectedOutput($text);
    $actual = $this->getOutput();

    if (str_contains((string) $actual, (string) $expected)) {
      throw new \RuntimeException(sprintf('Output contains "%s" but should not: %s', $expected, $actual));
    }
  }

  /**
   * Helper to print file comments.
   */
  protected static function behatCliPrintFileContents(string $filename, string $title = '') {
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
  protected static function behatCliIsDebug(): bool {
    return (bool) getenv('BEHAT_CLI_DEBUG');
  }

  /**
   * Helper to check if code coverage is enabled.
   */
  protected static function behatCliIsCoverageEnabled(): bool {
    return ini_get('pcov.enabled') === '1' && !empty(ini_get('pcov.directory'));
  }

  /**
   * Copy fixtures to the working directory.
   */
  protected function behatCliCopyFixtures() {
    $fixture_path_rel = 'tests/behat/fixtures';

    // @note Hardcoded path to the fixture directory.
    $fixture_path_abs = '/var/www/html/' . DIRECTORY_SEPARATOR . $fixture_path_rel;

    if (is_dir($fixture_path_abs)) {
      $dst = $this->workingDir . DIRECTORY_SEPARATOR . $fixture_path_rel;
      mkdir($dst, 0777, TRUE);

      foreach (glob($fixture_path_abs . '/*') as $file) {
        // @note Only copy files for speed.
        if (is_file($file)) {
          $filename = basename($file);
          copy($file, $dst . DIRECTORY_SEPARATOR . $filename);
        }
      }
    }
  }

}
