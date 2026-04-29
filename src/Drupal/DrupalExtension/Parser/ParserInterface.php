<?php

declare(strict_types=1);

namespace Drupal\DrupalExtension\Parser;

/**
 * Contract for cell-value parsers.
 *
 * Implementations translate a single Gherkin cell value into a list of
 * records consumable by Drupal field handlers. The parser deals with
 * format only: it knows nothing about Drupal entity types, field
 * definitions, or field validation. Callers iterate over a stub's
 * field/value pairs and decide which cells to send through the parser.
 */
interface ParserInterface {

  /**
   * Parses a Gherkin cell value.
   *
   * Each element of the returned list represents one item in the cell's
   * multi-value list. An item is either a scalar string (no compound
   * separator), a positional list of column values, or a map of named
   * column values, depending on the syntax detected in the cell.
   *
   * @param string $cell
   *   Raw cell text from the Gherkin table.
   * @param bool $multicolumn
   *   TRUE when the cell belongs to a multicolumn-header field
   *   ('field:column' / ':column' rows), FALSE otherwise. When TRUE,
   *   inline 'key: value' named-column parsing is suppressed (the column
   *   names come from the table headers instead).
   *
   * @return array<int, string|array<int|string, string>>
   *   The parsed list of records.
   */
  public function parse(string $cell, bool $multicolumn = FALSE): array;

}
