<?php

declare(strict_types=1);

namespace Drupal\DrupalExtension\Tests\Parser;

use Drupal\DrupalExtension\Parser\LegacyEntityFieldsParser;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Tests cell-level parsing in the legacy entity-fields parser.
 *
 * The legacy parser is format-only - it knows nothing about Drupal
 * entities, fields, or classifiers. These tests exercise the parsing
 * matrix without driver mocks; integration with field validation and
 * multicolumn-header tracking is exercised in 'RawDrupalContextTest'.
 */
#[CoversClass(LegacyEntityFieldsParser::class)]
class LegacyEntityFieldsParserTest extends TestCase {

  /**
   * Tests cell-level parsing in regular (non-multicolumn) mode.
   *
   * @param string $cell
   *   The raw cell value.
   * @param array<int, string|array<int|string, string>> $expected
   *   The expected list of records returned by the parser.
   */
  #[DataProvider('dataProviderParseRegular')]
  public function testParseRegular(string $cell, array $expected): void {
    $parser = new LegacyEntityFieldsParser();

    $this->assertSame($expected, $parser->parse($cell));
  }

  /**
   * Tests cell-level parsing in multicolumn mode.
   *
   * In multicolumn mode the inline 'key: value' named-column parsing is
   * suppressed because the column names come from the table headers.
   *
   * @param string $cell
   *   The raw cell value.
   * @param array<int, string|array<int|string, string>> $expected
   *   The expected list of records returned by the parser.
   */
  #[DataProvider('dataProviderParseMulticolumn')]
  public function testParseMulticolumn(string $cell, array $expected): void {
    $parser = new LegacyEntityFieldsParser();

    $this->assertSame($expected, $parser->parse($cell, multicolumn: TRUE));
  }

  /**
   * Provides data for testParseRegular().
   */
  public static function dataProviderParseRegular(): \Iterator {
    yield 'empty cell yields one empty record' => ['', ['']];
    yield 'single value' => ['A', ['A']];
    yield 'multiple csv values' => ['A, B, C', ['A', 'B', 'C']];
    yield 'csv with quoted comma' => [
      'A, "a value, containing a comma"',
      ['A', 'a value, containing a comma'],
    ];
    yield 'compound positional' => [
      'A - B',
      [['A', 'B']],
    ];
    yield 'inline named columns' => [
      'x: A - y: B',
      [['x' => 'A', 'y' => 'B']],
    ];
    yield 'multi-value positional compound' => [
      'A - B, C - D',
      [['A', 'B'], ['C', 'D']],
    ];
    yield 'multi-value named columns' => [
      'x: A - y: B, x: C - y: D',
      [['x' => 'A', 'y' => 'B'], ['x' => 'C', 'y' => 'D']],
    ];
    yield 'quoted value with compound separator preserved' => [
      '"Alpha - Bravo"',
      ['Alpha - Bravo'],
    ];
    yield 'quoted multi-value with compound separator preserved' => [
      '"Alpha - Bravo", "Charlie - Delta"',
      ['Alpha - Bravo', 'Charlie - Delta'],
    ];
    yield 'mixed quoted and unquoted compound values' => [
      '"Alpha - Bravo", C - D',
      ['Alpha - Bravo', ['C', 'D']],
    ];
  }

  /**
   * Provides data for testParseMulticolumn().
   */
  public static function dataProviderParseMulticolumn(): \Iterator {
    yield 'empty cell yields one empty record' => ['', ['']];
    yield 'single value' => ['value_a', ['value_a']];
    yield 'multiple csv values' => ['A1, A2', ['A1', 'A2']];
    yield 'csv with quoted comma' => [
      'A, "a value, containing a comma"',
      ['A', 'a value, containing a comma'],
    ];
    yield 'compound positional preserved' => [
      'A - B',
      [['A', 'B']],
    ];
    yield 'inline named columns are not parsed in multicolumn mode' => [
      'x: A - y: B',
      [['x: A', 'y: B']],
    ];
    yield 'multi-value positional compound' => [
      'A - B, C - D',
      [['A', 'B'], ['C', 'D']],
    ];
    yield 'quoted value with compound separator preserved' => [
      '"Alpha - Bravo"',
      ['Alpha - Bravo'],
    ];
  }

}
