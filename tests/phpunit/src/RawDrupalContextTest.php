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
   * Tests parsing entity fields.
   */
  #[DataProvider('dataProviderParseEntityFields')]
  public function testParseEntityFields(array $input, array $expected, ?array $fields = NULL, ?array $baseFields = NULL, ?string $exception = NULL, array $ignored_properties = []): void {
    if ($fields !== NULL) {
      $driver = $this->createMock(DriverInterface::class);
      $driver->method('isField')->willReturnCallback(
        fn(string $entityType, string $fieldName): bool => in_array($fieldName, $fields, TRUE)
      );
      $driver->method('isBaseField')->willReturnCallback(
        fn(string $entityType, string $fieldName): bool => in_array($fieldName, $baseFields ?? [], TRUE)
      );

      $drupal = $this->createMock(DrupalDriverManagerInterface::class);
      $drupal->method('getDriver')->willReturn($driver);

      $context = new RawDrupalContext();
      $context->setDrupal($drupal);
    }
    else {
      $context = $this->context;
    }

    if ($exception !== NULL) {
      $this->expectException(\RuntimeException::class);
      $this->expectExceptionMessage($exception);
    }

    $entity = (object) $input;
    $context->parseEntityFields('node', $entity, $ignored_properties);
    $this->assertSame($expected, (array) $entity);
  }

  /**
   * Provides data for testParseEntityFields().
   */
  public static function dataProviderParseEntityFields(): \Iterator {
    // All properties recognized as fields.
    yield 'single value' => [
      ['field_test' => 'A'],
      ['field_test' => ['A']],
    ];
    yield 'multiple csv values' => [
      ['field_test' => 'A, B, C'],
      ['field_test' => ['A', 'B', 'C']],
    ];
    yield 'csv with quoted comma' => [
      ['field_test' => 'A, "a value, containing a comma"'],
      ['field_test' => ['A', 'a value, containing a comma']],
    ];
    yield 'compound separator' => [
      ['field_test' => 'A - B'],
      ['field_test' => [['A', 'B']]],
    ];
    yield 'inline named columns' => [
      ['field_test' => 'x: A - y: B'],
      ['field_test' => [['x' => 'A', 'y' => 'B']]],
    ];
    yield 'multi-value compound' => [
      ['field_test' => 'A - B, C - D'],
      ['field_test' => [['A', 'B'], ['C', 'D']]],
    ];
    yield 'multi-value named columns' => [
      ['field_test' => 'x: A - y: B, x: C - y: D'],
      ['field_test' => [['x' => 'A', 'y' => 'B'], ['x' => 'C', 'y' => 'D']]],
    ];
    yield 'blank value unsets field' => [
      ['field_test' => ''],
      [],
    ];
    yield 'multiple fields on one entity' => [
      ['field_a' => 'X', 'field_b' => 'Y, Z'],
      ['field_a' => ['X'], 'field_b' => ['Y', 'Z']],
    ];
    yield 'multicolumn' => [
      ['field_test:col_a' => 'value_a', ':col_b' => 'value_b'],
      ['field_test' => [0 => ['col_a' => 'value_a', 'col_b' => 'value_b']]],
    ];
    yield 'multicolumn multiple values' => [
      ['field_test:col_a' => 'A1, A2', ':col_b' => 'B1, B2'],
      ['field_test' => [0 => ['col_a' => 'A1', 'col_b' => 'B1'], 1 => ['col_a' => 'A2', 'col_b' => 'B2']]],
    ];
    yield 'multicolumn blank values preserved' => [
      ['field_test:col_a' => '', ':col_b' => ''],
      ['field_test' => [0 => ['col_a' => '', 'col_b' => '']]],
    ];

    // Selective field recognition (base fields pass through unchanged).
    yield 'base field property left untouched' => [
      ['title' => 'Some title'],
      ['title' => 'Some title'],
      [],
      ['title'],
    ];
    yield 'base field with compound separator untouched' => [
      ['title' => 'A - B'],
      ['title' => 'A - B'],
      [],
      ['title'],
    ];
    yield 'multiple base field properties untouched' => [
      ['title' => 'Foo', 'status' => '1', 'uid' => '5'],
      ['title' => 'Foo', 'status' => '1', 'uid' => '5'],
      [],
      ['title', 'status', 'uid'],
    ];
    yield 'mixed field and base field properties' => [
      ['title' => 'Foo', 'field_test' => 'bar'],
      ['title' => 'Foo', 'field_test' => ['bar']],
      ['field_test'],
      ['title'],
    ];
    yield 'field parsed while base fields preserved' => [
      ['title' => 'Foo', 'field_a' => 'X - Y', 'field_b' => 'A, B', 'status' => '1'],
      ['title' => 'Foo', 'field_a' => [['X', 'Y']], 'field_b' => ['A', 'B'], 'status' => '1'],
      ['field_a', 'field_b'],
      ['title', 'status'],
    ];
    yield 'multicolumn base field left untouched' => [
      ['title:col_a' => 'value_a', ':col_b' => 'value_b'],
      ['title:col_a' => 'value_a', ':col_b' => 'value_b'],
      [],
      ['title'],
    ];

    // Exception cases.
    yield 'orphaned column throws' => [
      [':orphan' => 'value'],
      [],
      NULL,
      NULL,
      'Field name missing for :orphan',
    ];
    yield 'non-existent field throws' => [
      ['field_does_not_exist' => 'value'],
      [],
      [],
      [],
      'Field "field_does_not_exist" does not exist on entity type "node".',
    ];
    yield 'non-existent field among valid fields throws' => [
      ['title' => 'Foo', 'field_tags' => 'A', 'field_fake' => 'B'],
      [],
      ['field_tags'],
      ['title'],
      'Field "field_fake" does not exist on entity type "node".',
    ];
    yield 'non-existent multicolumn field throws' => [
      ['field_fake:col_a' => 'value'],
      [],
      [],
      [],
      'Field "field_fake" does not exist on entity type "node".',
    ];

    // Ignored properties.
    yield 'ignored property passes validation' => [
      ['role' => 'administrator', 'field_test' => 'A'],
      ['role' => 'administrator', 'field_test' => ['A']],
      ['field_test'],
      [],
      NULL,
      ['role'],
    ];
    yield 'multiple ignored properties pass validation' => [
      ['role' => 'editor', 'vocabulary_machine_name' => 'tags'],
      ['role' => 'editor', 'vocabulary_machine_name' => 'tags'],
      [],
      [],
      NULL,
      ['role', 'vocabulary_machine_name'],
    ];
  }

  /**
   * Tests that entity type is passed correctly to the driver.
   */
  public function testParseEntityFieldsPassesEntityType(): void {
    $driver = $this->createMock(DriverInterface::class);
    $driver->expects($this->once())
      ->method('isField')
      ->with('taxonomy_term', 'field_test')
      ->willReturn(TRUE);

    $drupal = $this->createMock(DrupalDriverManagerInterface::class);
    $drupal->method('getDriver')->willReturn($driver);

    $context = new RawDrupalContext();
    $context->setDrupal($drupal);

    $entity = (object) ['field_test' => 'value'];
    $context->parseEntityFields('taxonomy_term', $entity);
  }

}
