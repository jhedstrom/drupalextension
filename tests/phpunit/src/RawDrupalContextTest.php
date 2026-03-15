<?php

declare(strict_types=1);

namespace Drupal\DrupalExtension\Tests;

use Drupal\Driver\DriverInterface;
use Drupal\DrupalDriverManagerInterface;
use Drupal\DrupalExtension\Context\RawDrupalContext;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Tests the RawDrupalContext class.
 */
#[CoversClass(RawDrupalContext::class)]
class RawDrupalContextTest extends TestCase {

  /**
   * The context under test.
   */
  protected RawDrupalContext $context;

  /**
   * Sets up test fixtures.
   */
  protected function setUp(): void {
    $this->context = new RawDrupalContext();

    $driver = $this->createMock(DriverInterface::class);
    $driver->method('isField')->willReturn(TRUE);

    $drupal = $this->createMock(DrupalDriverManagerInterface::class);
    $drupal->method('getDriver')->willReturn($driver);

    $this->context->setDrupal($drupal);
  }

  /**
   * Tests parsing simple entity fields.
   */
  #[DataProvider('dataProviderParseEntityFieldsSimple')]
  public function testParseEntityFieldsSimple(string $input, array $expected): void {
    $entity = (object) ['field_test' => $input];
    $this->context->parseEntityFields('node', $entity);
    $this->assertSame($expected, $entity->field_test);
  }

  /**
   * Provides data for testParseEntityFieldsSimple().
   */
  public static function dataProviderParseEntityFieldsSimple(): \Iterator {
    yield 'single value' => [
      'A',
          ['A'],
    ];
    yield 'multiple csv values' => [
      'A, B, C',
          ['A', 'B', 'C'],
    ];
    yield 'csv with quoted comma' => [
      'A, "a value, containing a comma"',
          ['A', 'a value, containing a comma'],
    ];
  }

  /**
   * Tests parsing entity fields with compound separator.
   */
  public function testParseEntityFieldsCompoundSeparator(): void {
    $entity = (object) ['field_test' => 'A - B'];
    $this->context->parseEntityFields('node', $entity);
    $this->assertSame([['A', 'B']], $entity->field_test);
  }

  /**
   * Tests parsing entity fields with inline named columns.
   */
  public function testParseEntityFieldsInlineNamedColumns(): void {
    $entity = (object) ['field_test' => 'x: A - y: B'];
    $this->context->parseEntityFields('node', $entity);
    $this->assertSame([['x' => 'A', 'y' => 'B']], $entity->field_test);
  }

  /**
   * Tests parsing multi-value compound entity fields.
   */
  public function testParseEntityFieldsMultiValueCompound(): void {
    $entity = (object) ['field_test' => 'A - B, C - D'];
    $this->context->parseEntityFields('node', $entity);
    $this->assertSame([['A', 'B'], ['C', 'D']], $entity->field_test);
  }

  /**
   * Tests parsing multi-value named column entity fields.
   */
  public function testParseEntityFieldsMultiValueNamedColumns(): void {
    $entity = (object) ['field_test' => 'x: A - y: B, x: C - y: D'];
    $this->context->parseEntityFields('node', $entity);
    $this->assertSame(
          [['x' => 'A', 'y' => 'B'], ['x' => 'C', 'y' => 'D']],
          $entity->field_test
      );
  }

  /**
   * Tests that blank field values are unset.
   */
  public function testParseEntityFieldsBlankValueUnsets(): void {
    $entity = (object) ['field_test' => ''];
    $this->context->parseEntityFields('node', $entity);
    $this->assertObjectNotHasProperty('field_test', $entity);
  }

  /**
   * Tests parsing multicolumn entity fields.
   */
  public function testParseEntityFieldsMulticolumn(): void {
    $entity = (object) [
      'field_test:col_a' => 'value_a',
      ':col_b' => 'value_b',
    ];
    $this->context->parseEntityFields('node', $entity);
    $this->assertSame(
          [0 => ['col_a' => 'value_a', 'col_b' => 'value_b']],
          $entity->field_test
      );
  }

  /**
   * Tests parsing multicolumn entity fields with multiple values.
   */
  public function testParseEntityFieldsMulticolumnMultipleValues(): void {
    $entity = (object) [
      'field_test:col_a' => 'A1, A2',
      ':col_b' => 'B1, B2',
    ];
    $this->context->parseEntityFields('node', $entity);
    $this->assertSame(
          [
            0 => ['col_a' => 'A1', 'col_b' => 'B1'],
            1 => ['col_a' => 'A2', 'col_b' => 'B2'],
          ],
          $entity->field_test
      );
  }

  /**
   * Tests that orphaned columns throw an exception.
   */
  public function testParseEntityFieldsOrphanedColumnThrows(): void {
    $entity = (object) [':orphan' => 'value'];
    $this->expectException(\Exception::class);
    $this->expectExceptionMessage('Field name missing for :orphan');
    $this->context->parseEntityFields('node', $entity);
  }

  /**
   * Tests that non-field properties are left untouched.
   */
  public function testParseEntityFieldsNonFieldUntouched(): void {
    $driver = $this->createMock(DriverInterface::class);
    $driver->method('isField')->willReturn(FALSE);

    $drupal = $this->createMock(DrupalDriverManagerInterface::class);
    $drupal->method('getDriver')->willReturn($driver);

    $context = new RawDrupalContext();
    $context->setDrupal($drupal);

    $entity = (object) ['title' => 'Some title'];
    $context->parseEntityFields('node', $entity);
    $this->assertSame('Some title', $entity->title);
  }

  /**
   * Tests that blank values in multicolumn fields are preserved.
   */
  public function testParseEntityFieldsMulticolumnBlankValuePreserved(): void {
    $entity = (object) [
      'field_test:col_a' => '',
      ':col_b' => '',
    ];
    $this->context->parseEntityFields('node', $entity);
    $this->assertObjectHasProperty('field_test', $entity);
    $this->assertSame(
          [0 => ['col_a' => '', 'col_b' => '']],
          $entity->field_test
      );
  }

}
