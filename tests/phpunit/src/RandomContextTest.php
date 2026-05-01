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
   * Tests that 'RandomContext' implements the bare Behat context interface.
   *
   * Documents the contract that the class can be loaded in a suite that
   * does not register Mink or the Drupal extension.
   */
  public function testImplementsBareBehatContext(): void {
    $this->assertInstanceOf(Context::class, $this->context);
  }

  /**
   * Tests that the class no longer extends Drupal- or Mink-specific bases.
   *
   * Guards the structural promise of the rebase: no inherited 'getDriver()'
   * or 'getSession()' surface.
   */
  public function testHasNoDrupalOrMinkAncestor(): void {
    $this->assertSame([], class_parents($this->context));
  }

  /**
   * Tests that 'transformVariables()' returns the input when no tokens.
   */
  public function testTransformVariablesReturnsInputWhenNoTokens(): void {
    $this->valuesProperty->setValue($this->context, []);
    $this->assertSame('plain text', $this->context->transformVariables('plain text'));
  }

  /**
   * Tests that 'transformVariables()' substitutes a stored token.
   */
  public function testTransformVariablesSubstitutesToken(): void {
    $this->valuesProperty->setValue($this->context, ['<?token>' => 'abcdef']);
    $this->assertSame('value: abcdef', $this->context->transformVariables('value: <?token>'));
  }

  /**
   * Tests that the same token resolves to the same value across uses.
   */
  public function testTransformVariablesIsConsistentForRepeatedToken(): void {
    $this->valuesProperty->setValue($this->context, ['<?token>' => 'abcdef']);
    $this->assertSame('abcdef and abcdef', $this->context->transformVariables('<?token> and <?token>'));
  }

  /**
   * Tests that different tokens resolve to their own stored values.
   */
  public function testTransformVariablesUsesDistinctValuesForDistinctTokens(): void {
    $this->valuesProperty->setValue($this->context, [
      '<?one>' => 'aaaaaa',
      '<?two>' => 'bbbbbb',
    ]);
    $this->assertSame('aaaaaa and bbbbbb', $this->context->transformVariables('<?one> and <?two>'));
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
   * Tests that 'beforeScenarioSetVariables()' populates values from steps.
   */
  public function testBeforeScenarioPopulatesValuesFromScenarioSteps(): void {
    $scope = $this->createScenarioScope([
      $this->createStep('Given a string <?token>'),
    ]);

    $this->context->beforeScenarioSetVariables($scope);
    $values = $this->valuesProperty->getValue($this->context);

    $this->assertArrayHasKey('<?token>', $values);
    $this->assertMatchesRegularExpression('/^[a-z0-9]{10}$/', $values['<?token>']);
  }

  /**
   * Tests that the same token in multiple steps reuses the same value.
   */
  public function testBeforeScenarioReusesValuesForRepeatedTokens(): void {
    $scope = $this->createScenarioScope([
      $this->createStep('Given a string <?token>'),
      $this->createStep('Then I see <?token>'),
    ]);

    $this->context->beforeScenarioSetVariables($scope);
    $values = $this->valuesProperty->getValue($this->context);

    $this->assertCount(1, $values);
    $this->assertArrayHasKey('<?token>', $values);
  }

  /**
   * Tests that distinct tokens get distinct generated values.
   */
  public function testBeforeScenarioGeneratesDistinctValuesForDistinctTokens(): void {
    $scope = $this->createScenarioScope([
      $this->createStep('Given <?one> and <?two>'),
    ]);

    $this->context->beforeScenarioSetVariables($scope);
    $values = $this->valuesProperty->getValue($this->context);

    $this->assertCount(2, $values);
    $this->assertArrayHasKey('<?one>', $values);
    $this->assertArrayHasKey('<?two>', $values);
    $this->assertNotSame($values['<?one>'], $values['<?two>']);
  }

  /**
   * Tests that token values are lowercase and 10 chars long.
   */
  public function testBeforeScenarioGeneratesLowercaseValues(): void {
    $scope = $this->createScenarioScope([
      $this->createStep('Given <?token>'),
    ]);

    $this->context->beforeScenarioSetVariables($scope);
    $value = (string) $this->valuesProperty->getValue($this->context)['<?token>'];

    $this->assertSame(strtolower($value), $value);
    $this->assertSame(10, strlen($value));
  }

  /**
   * Tests that tokens in TableNode step arguments are picked up.
   */
  public function testBeforeScenarioPicksUpTokensFromStepTableArguments(): void {
    $table = new TableNode([
      ['title', '<?random_page>'],
    ]);
    $scope = $this->createScenarioScope([
      new StepNode('Given', 'a page with the following fields:', [$table], 1),
    ]);

    $this->context->beforeScenarioSetVariables($scope);
    $values = $this->valuesProperty->getValue($this->context);

    $this->assertArrayHasKey('<?random_page>', $values);
  }

  /**
   * Tests that tokens in background steps are picked up.
   */
  public function testBeforeScenarioPicksUpTokensFromBackgroundSteps(): void {
    $background = new BackgroundNode(NULL, [$this->createStep('Given <?from_background>')], 'Background', 1);
    $scope = $this->createScenarioScope([
      $this->createStep('Then <?from_scenario>'),
    ], $background);

    $this->context->beforeScenarioSetVariables($scope);
    $values = $this->valuesProperty->getValue($this->context);

    $this->assertArrayHasKey('<?from_background>', $values);
    $this->assertArrayHasKey('<?from_scenario>', $values);
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
   * Tests that a placeholder with regex special characters is handled.
   */
  #[DataProvider('dataProviderTransformVariablesEscapesRegexMetachars')]
  public function testTransformVariablesEscapesRegexMetachars(string $token, string $value, string $message, string $expected): void {
    $this->valuesProperty->setValue($this->context, [$token => $value]);
    $this->assertSame($expected, $this->context->transformVariables($message));
  }

  /**
   * Provides data for testTransformVariablesEscapesRegexMetachars().
   *
   * @return \Iterator<string, array<int, string>>
   *   Cases keyed by description, each [token, value, input message,
   *   expected message after substitution].
   */
  public static function dataProviderTransformVariablesEscapesRegexMetachars(): \Iterator {
    yield 'simple alphanumeric token' => ['<?abc>', 'value', '[<?abc>]', '[value]'];
    yield 'token with underscore' => ['<?my_token>', 'value', 'see <?my_token>', 'see value'];
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

  /**
   * Builds a 'StepNode' from raw text, defaulting to a 'Given' keyword.
   */
  protected function createStep(string $text): StepNode {
    return new StepNode('Given', $text, [], 1);
  }

}
