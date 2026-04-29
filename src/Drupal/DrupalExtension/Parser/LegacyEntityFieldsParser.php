<?php

declare(strict_types=1);

namespace Drupal\DrupalExtension\Parser;

/**
 * Legacy cell-value parser for entity fields.
 *
 * Implements the pre-v6 inline syntax: ',' separates multi-value items,
 * ' - ' separates compound columns, ': ' separates inline 'key: value'
 * named columns. Frozen at the 5.x behaviour and scheduled for removal
 * in 6.1; do not extend.
 */
class LegacyEntityFieldsParser implements ParserInterface {

  /**
   * {@inheritdoc}
   */
  public function parse(string $cell, bool $multicolumn = FALSE): array {
    $records = [];

    foreach (str_getcsv($cell, escape: "\\") as $key => $value) {
      $value = trim((string) $value);
      $columns = $value;
      // Skip splitting if the value was double-quoted in the original
      // cell, allowing values like "Alpha - Bravo" to pass through as-is
      // (e.g. entity reference titles with dashes).
      // @see https://github.com/jhedstrom/drupalextension/issues/642
      $was_quoted = str_contains($cell, '"' . $value . '"');

      if (!$was_quoted && str_contains($value, ' - ')) {
        $columns = [];

        foreach (explode(' - ', $value) as $column) {
          // Inline named columns. Suppressed in multicolumn cells where
          // names come from table headers instead.
          if (!$multicolumn && str_contains(substr($column, 1), ': ')) {
            [$inline_key, $column] = explode(': ', $column);
            $columns[$inline_key] = $column;
          }
          else {
            $columns[] = $column;
          }
        }
      }

      $records[$key] = $columns;
    }

    return $records;
  }

}
