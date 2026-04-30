<?php

declare(strict_types=1);

namespace Drupal\DrupalExtension\Tests\Parser;

use Drupal\Driver\Core\Field\FieldClassifierInterface;
use Drupal\DrupalExtension\Parser\LegacyEntityFieldParser;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Tests the legacy entity-field parser.
 *
 * The legacy parser owns the full parsing-and-validation pipeline:
 * textual splitting, multicolumn-header merging, field-type
 * classification, and the unknown-field guard. Tests drive it directly
 * with a stub classifier.
 */
#[CoversClass(LegacyEntityFieldParser::class)]
class LegacyEntityFieldParserTest extends TestCase {

  /**
   * Tests the parsing matrix.
   *
   * @param array<string|int, mixed> $input
   *   The raw stub values.
   * @param array<string, mixed> $expected
   *   The expected parsed output.
   * @param array<int, string>|null $configurable_fields
   *   Field names the classifier should report as configurable, or NULL
   *   to treat every field as configurable (default).
   * @param array<int, string>|null $base_fields
   *   Field names the classifier should report as base fields.
   * @param string|null $exception
   *   Expected exception message, when the call should throw.
   * @param array<int, string> $ignored_properties
   *   Property names the parser should accept without validation.
   */
  #[DataProvider('dataProviderParse')]
  public function testParse(
    array $input,
    array $expected,
    ?array $configurable_fields = NULL,
    ?array $base_fields = NULL,
    ?string $exception = NULL,
    array $ignored_properties = [],
  ): void {
    $classifier = $this->createMock(FieldClassifierInterface::class);

    if ($configurable_fields === NULL) {
      $classifier->method('fieldIsConfigurable')->willReturn(TRUE);
    }
    else {
      $classifier->method('fieldIsConfigurable')->willReturnCallback(
        fn(string $entity_type, string $field_name): bool => in_array($field_name, $configurable_fields, TRUE)
      );
      $is_base = fn(string $entity_type, string $field_name): bool => in_array($field_name, $base_fields ?? [], TRUE);
      $classifier->method('fieldIsBaseStandard')->willReturnCallback($is_base);
      $classifier->method('fieldIsBaseComputedReadOnly')->willReturnCallback($is_base);
      $classifier->method('fieldIsBaseComputedWritable')->willReturnCallback($is_base);
      $classifier->method('fieldIsBaseCustomStorage')->willReturnCallback($is_base);
    }

    $parser = (new LegacyEntityFieldParser('node', $classifier))->ignoring($ignored_properties);

    if ($exception !== NULL) {
      $this->expectException(\RuntimeException::class);
      $this->expectExceptionMessage($exception);
    }

    $this->assertSame($expected, $parser->parse($input));
  }

  /**
   * Tests that non-F1 base fields pass through without throwing.
   *
   * Regression guard: every base F-row predicate must be checked so
   * computed-writable base fields like 'moderation_state' (F3) do not
   * trigger the unknown-field error.
   */
  #[DataProvider('dataProviderParseAcceptsNonStandardBaseFields')]
  public function testParseAcceptsNonStandardBaseFields(string $true_predicate): void {
    $base_predicates = [
      'fieldIsBaseStandard',
      'fieldIsBaseComputedReadOnly',
      'fieldIsBaseComputedWritable',
      'fieldIsBaseCustomStorage',
    ];

    $classifier = $this->createMock(FieldClassifierInterface::class);
    $classifier->method('fieldIsConfigurable')->willReturn(FALSE);

    foreach ($base_predicates as $predicate) {
      $classifier->method($predicate)->willReturn($predicate === $true_predicate);
    }

    $parser = new LegacyEntityFieldParser('node', $classifier);

    $this->assertSame(
      ['moderation_state' => 'draft'],
      $parser->parse(['moderation_state' => 'draft']),
    );
  }

  /**
   * Tests that the configured entity type is passed to the classifier.
   */
  public function testParsePassesEntityType(): void {
    $classifier = $this->createMock(FieldClassifierInterface::class);
    $classifier->expects($this->once())
      ->method('fieldIsConfigurable')
      ->with('taxonomy_term', 'field_test')
      ->willReturn(TRUE);

    $parser = new LegacyEntityFieldParser('taxonomy_term', $classifier);
    $parser->parse(['field_test' => 'value']);
  }

  /**
   * Provides data for testParse().
   */
  public static function dataProviderParse(): \Iterator {
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
    yield 'quoted value with compound separator preserved' => [
      ['field_test' => '"Alpha - Bravo"'],
      ['field_test' => ['Alpha - Bravo']],
    ];
    yield 'quoted multi-value with compound separator preserved' => [
      ['field_test' => '"Alpha - Bravo", "Charlie - Delta"'],
      ['field_test' => ['Alpha - Bravo', 'Charlie - Delta']],
    ];
    yield 'mixed quoted and unquoted values' => [
      ['field_test' => '"Alpha - Bravo", C - D'],
      ['field_test' => ['Alpha - Bravo', ['C', 'D']]],
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
   * Provides data for testParseAcceptsNonStandardBaseFields().
   */
  public static function dataProviderParseAcceptsNonStandardBaseFields(): \Iterator {
    yield 'F1 base standard' => ['fieldIsBaseStandard'];
    yield 'F2 base computed read-only' => ['fieldIsBaseComputedReadOnly'];
    yield 'F3 base computed writable' => ['fieldIsBaseComputedWritable'];
    yield 'F4 base custom storage' => ['fieldIsBaseCustomStorage'];
  }

}
