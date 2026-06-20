<?php

declare(strict_types=1);

namespace Drupal\DrupalExtension\Tests;

use Behat\Behat\Context\Context;
use Behat\Gherkin\Node\TableNode;
use Drupal\DrupalExtension\Context\MappingContext;
use Drupal\DrupalExtension\ParametersAwareInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Tests the MappingContext class.
 *
 * The class is exercised standalone - no Behat extension, no Mink session,
 * no Drupal driver - to prove that none of those subsystems are required
 * to load and use 'MappingContext'.
 */
#[CoversClass(MappingContext::class)]
class MappingContextTest extends TestCase {

  /**
   * The context under test, primed with a known mapping table.
   */
  protected MappingContext $context;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    $this->context = new MappingContext();
    $this->context->setParameters([
      'mappings' => [
        'User Registration' => '/user/register',
        'User Login' => '/user/login',
        'Greeting' => 'Hello World',
      ],
    ]);
  }

  /**
   * Tests the bare-Behat structural promise.
   *
   * 'MappingContext' must implement 'Behat\Behat\Context\Context' directly
   * with no parent class, so it can be loaded in suites that register
   * neither Mink nor the Drupal extension.
   */
  public function testImplementsBareBehatContextWithoutAncestors(): void {
    $context = new MappingContext();
    $this->assertInstanceOf(Context::class, $context);
    $this->assertInstanceOf(ParametersAwareInterface::class, $context);
    $this->assertSame([], class_parents($context));
  }

  /**
   * Tests '{{ Key }}' substitution inside a scalar argument.
   *
   * @param string $argument
   *   The raw step argument.
   * @param string $expected
   *   The argument after every token has been resolved.
   */
  #[DataProvider('dataProviderTransformMappingsSubstitutesTokens')]
  public function testTransformMappingsSubstitutesTokens(string $argument, string $expected): void {
    $this->assertSame($expected, $this->context->transformMappings($argument));
  }

  /**
   * Provides cases for testTransformMappingsSubstitutesTokens().
   */
  public static function dataProviderTransformMappingsSubstitutesTokens(): \Iterator {
    yield 'bare token resolves' => ['{{User Registration}}', '/user/register'];
    yield 'token with inner whitespace resolves the same' => ['{{ User Registration }}', '/user/register'];
    yield 'token embedded in surrounding text' => ['go to {{ User Registration }} now', 'go to /user/register now'];
    yield 'two distinct tokens both resolve' => [
      '{{User Login}} then {{User Registration}}',
      '/user/login then /user/register',
    ];
    yield 'a repeated token resolves every occurrence' => ['{{Greeting}}, {{Greeting}}!', 'Hello World, Hello World!'];
    yield 'a string without tokens is returned unchanged' => ['plain value', 'plain value'];
  }

  /**
   * Tests that whitespace immediately inside the braces is ignored.
   */
  public function testWhitespaceInsideBracesIsIgnored(): void {
    $this->assertSame(
      $this->context->transformMappings('{{User Registration}}'),
      $this->context->transformMappings('{{   User Registration   }}'),
    );
  }

  /**
   * Tests that an unknown key fails the step instead of passing through.
   */
  public function testUnknownKeyThrows(): void {
    $this->expectException(\RuntimeException::class);
    $this->expectExceptionMessage('No such mapping: Missing Page');
    $this->context->transformMappings('{{ Missing Page }}');
  }

  /**
   * Tests that 'transformMappingsTable()' substitutes tokens in cells only.
   */
  public function testTransformTableSubstitutesTokensInCells(): void {
    $table = new TableNode([
      ['name', 'path'],
      ['Registration', '{{ User Registration }}'],
      ['Login', '{{User Login}}'],
    ]);

    $rows = $this->context->transformMappingsTable($table)->getRows();

    $this->assertSame(['name', 'path'], $rows[0]);
    $this->assertSame(['Registration', '/user/register'], $rows[1]);
    $this->assertSame(['Login', '/user/login'], $rows[2]);
  }

}
