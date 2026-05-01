<?php

declare(strict_types=1);

namespace Drupal\DrupalExtension\Tests;

use Behat\Behat\Context\Context;
use Behat\Behat\Hook\Scope\ScenarioScope;
use Behat\Gherkin\Node\BackgroundNode;
use Behat\Gherkin\Node\FeatureNode;
use Behat\Gherkin\Node\ScenarioNode;
use Behat\Gherkin\Node\StepNode;
use Behat\Gherkin\Node\TableNode;
use Drupal\DrupalExtension\Context\RandomContext;
use Drupal\DrupalExtension\DeprecationInterface;
use Drupal\DrupalExtension\ParametersAwareInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Tests the RandomContext class.
 *
 * The class is exercised standalone - no Behat extension, no Mink session,
 * no Drupal driver - to prove that none of those subsystems are required
 * to load and use 'RandomContext'.
 */
#[CoversClass(RandomContext::class)]
class RandomContextTest extends TestCase {

  /**
   * The context under test - a subclass that captures deprecations.
   */
  protected RecordingRandomContext $context;

  /**
   * Reflection of the protected $values property (canonical key -> value).
   */
  protected \ReflectionProperty $valuesProperty;

  /**
   * Reflection of the protected $literals property (literal -> canonical).
   */
  protected \ReflectionProperty $literalsProperty;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    $this->context = new RecordingRandomContext();
    $this->valuesProperty = new \ReflectionProperty(RandomContext::class, 'values');
    $this->literalsProperty = new \ReflectionProperty(RandomContext::class, 'literals');
  }

  /**
   * Tests the bare-Behat structural promise of the rebase.
   *
   * 'RandomContext' must implement 'Behat\Behat\Context\Context' directly
   * with no parent class, so it can be loaded in suites that do not
   * register Mink or the Drupal extension.
   */
  public function testImplementsBareBehatContextWithoutAncestors(): void {
    $context = new RandomContext();
    $this->assertInstanceOf(Context::class, $context);
    $this->assertInstanceOf(ParametersAwareInterface::class, $context);
    $this->assertInstanceOf(DeprecationInterface::class, $context);
    $this->assertSame([], class_parents($context));
  }

  /**
   * Tests modern '[?...]' substitution via 'transformVariables()'.
   *
   * @param string $message
   *   The input message containing one or more modern tokens.
   * @param string $pattern
   *   Regex the substituted output must match end-to-end.
   * @param int $expected_substitutions
   *   How many distinct token literals should be present in the output map.
   */
  #[DataProvider('dataProviderTransformVariablesSubstitutesTokens')]
  public function testTransformVariablesSubstitutesTokens(string $message, string $pattern, int $expected_substitutions): void {
    $output = $this->context->transformVariables($message);
    $this->assertIsString($output);
    $this->assertMatchesRegularExpression($pattern, $output);
    $this->assertCount($expected_substitutions, $this->literalsProperty->getValue($this->context));
  }

  /**
   * Provides cases for testTransformVariablesSubstitutesTokens().
   *
   * Modern-form inputs only - legacy '<?...>' is covered separately by
   * 'testTransformVariablesLegacySubstitutesTokens()'. Pattern is anchored
   * end-to-end so a wrongly-substituted output fails.
   */
  public static function dataProviderTransformVariablesSubstitutesTokens(): \Iterator {
    yield 'bare token resolves to lowercase 10-char string' => [
      'value: [?token]',
      '/^value: [a-z0-9]{10}$/',
      1,
    ];
    yield 'typed token with explicit length' => [
      'value: [?token:string,5]',
      '/^value: [a-z0-9]{5}$/',
      1,
    ];
    yield 'machine_name produces snake-shaped string' => [
      'value: [?slug:machine_name,8]',
      '/^value: [a-z0-9_]{8}$/',
      1,
    ];
    yield 'int produces digits within range' => [
      'value: [?age:int,18,65]',
      '/^value: (1[89]|[2-5]\d|6[0-5])$/',
      1,
    ];
    yield 'email produces address at .test' => [
      'value: [?contact:email]',
      '/^value: [a-z0-9]+@[a-z0-9]+\.test$/',
      1,
    ];
    yield 'uuid produces v4 hex' => [
      'value: [?id:uuid]',
      '/^value: [0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/',
      1,
    ];
    yield 'two distinct names yield two distinct cache entries' => [
      '[?one] and [?two]',
      '/^[a-z0-9]{10} and [a-z0-9]{10}$/',
      2,
    ];
  }

  /**
   * Tests legacy '<?...>' substitution via 'transformVariablesLegacy()'.
   *
   * Asserts both that the substitution works and that exactly one
   * deprecation message is captured per unique legacy literal.
   *
   * @param string $message
   *   The input message containing legacy tokens.
   * @param string $pattern
   *   Regex the substituted output must match end-to-end.
   * @param int $expected_deprecations
   *   How many '[Deprecation]' notices should be captured.
   */
  #[DataProvider('dataProviderTransformVariablesLegacySubstitutesTokens')]
  public function testTransformVariablesLegacySubstitutesTokens(string $message, string $pattern, int $expected_deprecations): void {
    $output = $this->context->transformVariablesLegacy($message);
    $this->assertIsString($output);
    $this->assertMatchesRegularExpression($pattern, $output);
    $this->assertCount($expected_deprecations, $this->context->capturedDeprecations);
  }

  /**
   * Provides cases for testTransformVariablesLegacySubstitutesTokens().
   */
  public static function dataProviderTransformVariablesLegacySubstitutesTokens(): \Iterator {
    yield 'single legacy token resolves and emits one deprecation' => [
      'value: <?token>',
      '/^value: [a-z0-9]{10}$/',
      1,
    ];
    yield 'two distinct legacy tokens emit two deprecations' => [
      '<?one> and <?two>',
      '/^[a-z0-9]{10} and [a-z0-9]{10}$/',
      2,
    ];
  }

  /**
   * Tests that equivalent token forms collapse to the same canonical value.
   *
   * Default 'string' / length '10' means '[?title]', '[?title:string]',
   * '[?title:string,10]', and the legacy '<?title>' must all resolve to
   * the same string within one scenario - this is the back-compat lever
   * that lets users migrate one literal at a time.
   */
  public function testEquivalentFormsShareTheSameValue(): void {
    $message = '<?title> [?title] [?title:string] [?title:string,10]';
    $modern = $this->context->transformVariables($message);
    $this->assertIsString($modern);
    $output = $this->context->transformVariablesLegacy($modern);
    $this->assertIsString($output);

    $parts = explode(' ', $output);
    $this->assertCount(4, $parts);
    $this->assertSame([$parts[0]], array_unique($parts));
  }

  /**
   * Tests that distinct types under the same name yield distinct values.
   */
  public function testDistinctTypesUnderSameNameDoNotCollide(): void {
    $output = $this->context->transformVariables('[?id] [?id:int,1,9999]');
    $this->assertIsString($output);

    [$default, $integer] = explode(' ', $output);
    $this->assertMatchesRegularExpression('/^[a-z0-9]{10}$/', $default);
    $this->assertMatchesRegularExpression('/^[1-9]\d{0,3}$/', $integer);
  }

  /**
   * Tests that an unknown type raises a clear error.
   */
  public function testUnknownTypeThrows(): void {
    $this->expectException(\InvalidArgumentException::class);
    $this->expectExceptionMessage('Unknown random token type "nope"');
    $this->context->transformVariables('[?x:nope]');
  }

  /**
   * Tests that 'transformTable()' substitutes modern tokens in cells.
   */
  public function testTransformTableSubstitutesModernTokensInCells(): void {
    $table = new TableNode([
      ['title', 'value'],
      ['[?one]', '[?two]'],
    ]);

    $transformed = $this->context->transformTable($table);
    $rows = $transformed->getRows();

    $this->assertSame(['title', 'value'], $rows[0]);
    $this->assertMatchesRegularExpression('/^[a-z0-9]{10}$/', $rows[1][0]);
    $this->assertMatchesRegularExpression('/^[a-z0-9]{10}$/', $rows[1][1]);
    $this->assertNotSame($rows[1][0], $rows[1][1]);
    $this->assertSame([], $this->context->capturedDeprecations);
  }

  /**
   * Tests that 'transformTableLegacy()' substitutes and emits deprecations.
   */
  public function testTransformTableLegacySubstitutesAndDeprecates(): void {
    $table = new TableNode([
      ['title', 'value'],
      ['<?one>', '<?two>'],
    ]);

    $transformed = $this->context->transformTableLegacy($table);
    $rows = $transformed->getRows();

    $this->assertSame(['title', 'value'], $rows[0]);
    $this->assertMatchesRegularExpression('/^[a-z0-9]{10}$/', $rows[1][0]);
    $this->assertMatchesRegularExpression('/^[a-z0-9]{10}$/', $rows[1][1]);
    $this->assertCount(2, $this->context->capturedDeprecations);
  }

  /**
   * Tests scenario pre-resolution covers steps, tables, and backgrounds.
   *
   * Each entry asserts the canonical cache keys produced after the hook
   * runs, which is the post-normalisation contract.
   *
   * @param list<string> $expected_keys
   *   Canonical keys that should be present in the values map.
   * @param list<\Behat\Gherkin\Node\StepNode> $scenario_steps
   *   Scenario steps to attach to the constructed scope.
   * @param list<\Behat\Gherkin\Node\StepNode>|null $background_steps
   *   Optional background steps; NULL omits the background.
   */
  #[DataProvider('dataProviderBeforeScenarioCollectsTokens')]
  public function testBeforeScenarioCollectsTokens(array $expected_keys, array $scenario_steps, ?array $background_steps = NULL): void {
    $background = $background_steps === NULL ? NULL : new BackgroundNode(NULL, $background_steps, 'Background', 1);
    $scope = $this->createScenarioScope($scenario_steps, $background);

    $this->context->beforeScenarioSetVariables($scope);
    $values = $this->valuesProperty->getValue($this->context);

    $this->assertSame($expected_keys, array_keys($values));
  }

  /**
   * Provides cases for testBeforeScenarioCollectsTokens().
   */
  public static function dataProviderBeforeScenarioCollectsTokens(): \Iterator {
    yield 'legacy token in step text' => [
      ['token:string:10'],
      [new StepNode('Given', 'a string <?token>', [], 1)],
    ];
    yield 'new bare token in step text' => [
      ['token:string:10'],
      [new StepNode('Given', 'a string [?token]', [], 1)],
    ];
    yield 'mixed legacy and new with same name share one key' => [
      ['token:string:10'],
      [
        new StepNode('Given', 'first <?token>', [], 1),
        new StepNode('Then', 'second [?token]', [], 2),
      ],
    ];
    yield 'distinct names produce distinct keys' => [
      ['one:string:10', 'two:string:10'],
      [new StepNode('Given', '[?one] and [?two]', [], 1)],
    ];
    yield 'typed token in TableNode argument is picked up' => [
      ['slug:machine_name:6'],
      [new StepNode('Given', 'a page with the following fields:', [new TableNode([['title', '[?slug:machine_name,6]']])], 1)],
    ];
    yield 'tokens in background and scenario are merged' => [
      ['from_background:string:10', 'from_scenario:string:10'],
      [new StepNode('Then', '[?from_scenario]', [], 2)],
      [new StepNode('Given', '<?from_background>', [], 1)],
    ];
  }

  /**
   * Tests that legacy literals trigger one deprecation each.
   *
   * The trait's process-wide dedup means each unique message fires only
   * once, so two uses of '<?token>' produce one notice but '<?one>' plus
   * '<?two>' produce two. Deprecation is the legacy Transform method's
   * responsibility - 'resolveLiteral()' must not fire on its own.
   */
  public function testLegacyTransformTriggersDeprecation(): void {
    $this->context->transformVariablesLegacy('<?one> <?two> <?one>');
    $messages = $this->context->capturedDeprecations;

    $this->assertCount(2, $messages);
    $this->assertStringContainsString('"<?one>" token syntax is deprecated', $messages[0]);
    $this->assertStringContainsString('Use "[?one]" instead', $messages[0]);
    $this->assertStringContainsString('"<?two>" token syntax is deprecated', $messages[1]);
    $this->assertStringContainsString('Use "[?two]" instead', $messages[1]);
  }

  /**
   * Tests that the modern Transform method never emits a deprecation.
   */
  public function testModernTransformDoesNotTriggerDeprecation(): void {
    $this->context->transformVariables('[?one] [?two:int,1,9]');

    $this->assertSame([], $this->context->capturedDeprecations);
  }

  /**
   * Tests that scenario pre-resolution does not emit deprecations.
   *
   * The 'BeforeScenario' hook warms the cache via 'resolveLiteral()'; if
   * deprecation lived there, every legacy literal in a feature would fire
   * a notice before any step ran. The legacy Transform methods are the
   * single source of deprecation emission.
   */
  public function testBeforeScenarioDoesNotTriggerDeprecation(): void {
    $scope = $this->createScenarioScope([
      new StepNode('Given', 'a string <?legacy>', [], 1),
      new StepNode('Then', '[?modern]', [], 2),
    ]);

    $this->context->beforeScenarioSetVariables($scope);

    $this->assertSame([], $this->context->capturedDeprecations);
  }

  /**
   * Tests that 'afterScenarioResetVariables()' clears the caches.
   */
  public function testAfterScenarioClearsValues(): void {
    $this->context->transformVariables('[?token]');
    $this->assertNotSame([], $this->valuesProperty->getValue($this->context));

    $scope = $this->createScenarioScope([]);
    $this->context->afterScenarioResetVariables($scope);

    $this->assertSame([], $this->valuesProperty->getValue($this->context));
    $this->assertSame([], $this->literalsProperty->getValue($this->context));
  }

  /**
   * Builds a 'ScenarioScope' over a fixed list of scenario steps.
   *
   * @param array<int, \Behat\Gherkin\Node\StepNode> $steps
   *   Steps to attach to the scenario.
   * @param \Behat\Gherkin\Node\BackgroundNode|null $background
   *   Optional background to attach to the feature.
   */
  protected function createScenarioScope(array $steps, ?BackgroundNode $background = NULL): ScenarioScope {
    $scenario = new ScenarioNode('Scenario title', [], $steps, 'Scenario', 1);
    $feature = new FeatureNode(
      'Feature title',
      NULL,
      [],
      $background,
      [$scenario],
      'Feature',
      'en',
      NULL,
      1,
    );

    $scope = $this->createMock(ScenarioScope::class);
    $scope->method('getFeature')->willReturn($feature);
    $scope->method('getScenario')->willReturn($scenario);

    return $scope;
  }

}

/**
 * Subclass that records deprecations instead of writing to 'STDERR'.
 *
 * 'DeprecationTrait::triggerDeprecation()' writes through 'fwrite(STDERR,
 * ...)' which sidesteps PHP output buffering, so capturing it from a
 * unit test is awkward. Overriding the method is the simplest hook.
 */
// phpcs:ignore Drupal.Classes.ClassFileName.NoMatch
class RecordingRandomContext extends RandomContext {

  /**
   * Deprecation messages captured during the test.
   *
   * @var list<string>
   */
  public array $capturedDeprecations = [];

  /**
   * {@inheritdoc}
   */
  public function triggerDeprecation(string $message): void {
    $this->capturedDeprecations[] = $message;
  }

}
