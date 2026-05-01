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
   * The context under test.
   */
  protected RandomContext $context;

  /**
   * Reflection of the protected $values property.
   */
  protected \ReflectionProperty $valuesProperty;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    $this->context = new RandomContext();
    $this->valuesProperty = new \ReflectionProperty(RandomContext::class, 'values');
  }

  /**
   * Tests the bare-Behat structural promise of the rebase.
   *
   * 'RandomContext' must implement 'Behat\Behat\Context\Context' directly
   * with no parent class, so it can be loaded in suites that do not
   * register Mink or the Drupal extension.
   */
  public function testImplementsBareBehatContextWithoutAncestors(): void {
    $this->assertInstanceOf(Context::class, $this->context);
    $this->assertSame([], class_parents($this->context));
  }

  /**
   * Tests 'transformVariables()' substitution behaviour.
   *
   * @param array<string, string> $values
   *   Token-to-value map to seed before invoking the transform.
   * @param string $message
   *   The input message to transform.
   * @param string $expected
   *   The expected message after substitution.
   */
  #[DataProvider('dataProviderTransformVariables')]
  public function testTransformVariables(array $values, string $message, string $expected): void {
    $this->valuesProperty->setValue($this->context, $values);
    $this->assertSame($expected, $this->context->transformVariables($message));
  }

  /**
   * Provides data for testTransformVariables().
   *
   * @return \Iterator<string, array{array<string, string>, string, string}>
   *   Cases keyed by description, each [stored values, input message,
   *   expected output].
   */
  public static function dataProviderTransformVariables(): \Iterator {
    yield 'no tokens returns input unchanged' => [
      [],
      'plain text',
      'plain text',
    ];
    yield 'single token is substituted' => [
      ['<?token>' => 'abcdef'],
      'value: <?token>',
      'value: abcdef',
    ];
    yield 'repeated token resolves to same value' => [
      ['<?token>' => 'abcdef'],
      '<?token> and <?token>',
      'abcdef and abcdef',
    ];
    yield 'distinct tokens resolve to their own values' => [
      ['<?one>' => 'aaaaaa', '<?two>' => 'bbbbbb'],
      '<?one> and <?two>',
      'aaaaaa and bbbbbb',
    ];
    yield 'surrounding characters are preserved' => [
      ['<?abc>' => 'value'],
      '[<?abc>]',
      '[value]',
    ];
    yield 'identifier with underscore is supported' => [
      ['<?my_token>' => 'value'],
      'see <?my_token>',
      'see value',
    ];
  }

  /**
   * Tests that 'transformTable()' substitutes tokens in cells.
   */
  public function testTransformTableSubstitutesTokensInCells(): void {
    $this->valuesProperty->setValue($this->context, ['<?title>' => 'mytitle']);

    $table = new TableNode([
      ['title', 'value'],
      ['<?title>', 'static'],
    ]);

    $transformed = $this->context->transformTable($table);

    $this->assertSame([
      ['title', 'value'],
      ['mytitle', 'static'],
    ], $transformed->getRows());
  }

  /**
   * Tests that 'beforeScenarioSetVariables()' collects tokens from steps.
   *
   * Covers tokens in step text, in 'TableNode' step arguments, and in
   * background steps; same-token reuse and distinct-token uniqueness.
   *
   * @param list<string> $expected_tokens
   *   Tokens expected to be present in the populated values map, in
   *   order of first appearance.
   * @param list<\Behat\Gherkin\Node\StepNode> $scenario_steps
   *   Scenario steps to attach to the constructed scope.
   * @param list<\Behat\Gherkin\Node\StepNode>|null $background_steps
   *   Optional background steps; NULL omits the background.
   */
  #[DataProvider('dataProviderBeforeScenarioCollectsTokens')]
  public function testBeforeScenarioCollectsTokens(array $expected_tokens, array $scenario_steps, ?array $background_steps = NULL): void {
    $background = $background_steps === NULL ? NULL : new BackgroundNode(NULL, $background_steps, 'Background', 1);
    $scope = $this->createScenarioScope($scenario_steps, $background);

    $this->context->beforeScenarioSetVariables($scope);
    $values = $this->valuesProperty->getValue($this->context);

    $this->assertSame($expected_tokens, array_keys($values));
    foreach ($values as $value) {
      $this->assertMatchesRegularExpression('/^[a-z0-9]{10}$/', (string) $value);
    }
    if (count($expected_tokens) > 1) {
      $this->assertSame(count($expected_tokens), count(array_unique($values)));
    }
  }

  /**
   * Provides data for testBeforeScenarioCollectsTokens().
   *
   * @return \Iterator<string, array{0: list<string>, 1: list<\Behat\Gherkin\Node\StepNode>, 2?: list<\Behat\Gherkin\Node\StepNode>|null}>
   *   Cases keyed by description, each [expected token list, scenario
   *   steps, optional background steps].
   */
  public static function dataProviderBeforeScenarioCollectsTokens(): \Iterator {
    yield 'token in scenario step text' => [
      ['<?token>'],
      [new StepNode('Given', 'a string <?token>', [], 1)],
    ];
    yield 'repeated token across steps reuses one value' => [
      ['<?token>'],
      [
        new StepNode('Given', 'a string <?token>', [], 1),
        new StepNode('Then', 'I see <?token>', [], 2),
      ],
    ];
    yield 'distinct tokens in one step yield distinct values' => [
      ['<?one>', '<?two>'],
      [new StepNode('Given', '<?one> and <?two>', [], 1)],
    ];
    yield 'token in TableNode step argument is picked up' => [
      ['<?random_page>'],
      [new StepNode('Given', 'a page with the following fields:', [new TableNode([['title', '<?random_page>']])], 1)],
    ];
    yield 'tokens in background and scenario steps are merged' => [
      ['<?from_background>', '<?from_scenario>'],
      [new StepNode('Then', '<?from_scenario>', [], 2)],
      [new StepNode('Given', '<?from_background>', [], 1)],
    ];
  }

  /**
   * Tests that 'afterScenarioResetVariables()' clears the stored map.
   */
  public function testAfterScenarioClearsValues(): void {
    $this->valuesProperty->setValue($this->context, ['<?token>' => 'abcdef']);

    $scope = $this->createScenarioScope([]);
    $this->context->afterScenarioResetVariables($scope);

    $this->assertSame([], $this->valuesProperty->getValue($this->context));
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
