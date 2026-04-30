<?php

declare(strict_types=1);

namespace Drupal\DrupalExtension\Parser;

use Drupal\Driver\Core\Field\FieldClassifierInterface;
use Drupal\DrupalExtension\Parser\Exception\MultipleParseException;
use Drupal\DrupalExtension\Parser\Exception\ParseException;

/**
 * Modern entity-field parser.
 *
 * Implements a syntax with a single uniform escape mechanism (double
 * quotes) for compound values. Cells fall into two modes detected by the
 * value form, not by the spacing of separators:
 *
 *   Scalar mode (no top-level 'key:"...' or 'key:[...]' pattern):
 *     - Plain text or comma-separated list of items.
 *     - Items containing ',' or ';' or '"' must be wrapped in '"..."'.
 *
 *   Compound mode (top-level 'key:"...' or 'key:[...]' pattern present):
 *     - One or more 'key: value' columns separated by ','.
 *     - Multi-value compound: records separated by ';'.
 *     - Each column value MUST be a quoted string ('"..."') or token
 *       ('[name:value]'). Bare values are a parse error.
 *     - Inside '"..."': '\"' '\\' '\n' '\t' '\r' are recognised escape
 *       sequences; any other backslash sequence is an error.
 *
 * Whitespace around ',', ';' and ':' is ignored outside quoted strings
 * and tokens. Whitespace inside '"..."' is preserved literally.
 *
 * Errors detected while parsing a single cell are collected and thrown
 * together via 'MultipleParseException' so authors see every problem at
 * once instead of fixing them one at a time.
 */
final class EntityFieldParser implements EntityFieldParserInterface {

  /**
   * Property names accepted without field-type validation.
   *
   * @var string[]
   */
  protected array $ignoredProperties = [];

  /**
   * Constructs the parser for one entity-type / classifier pairing.
   */
  public function __construct(
    protected readonly string $entityType,
    protected readonly FieldClassifierInterface $fieldClassifier,
  ) {
  }

  /**
   * {@inheritdoc}
   */
  public function ignoring(array $properties): static {
    $this->ignoredProperties = $properties;

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function parse(array $values): array {
    $multicolumn_field = '';
    $multicolumn_column = '';
    $multicolumn_fields = [];
    $parsed = [];
    $errors = [];

    foreach ($values as $field => $field_value) {
      $field = (string) $field;

      if (!str_contains($field, ':')) {
        $multicolumn_field = '';
        $multicolumn_column = '';
      }
      elseif (str_contains(substr($field, 1), ':')) {
        [$multicolumn_field, $multicolumn_column] = explode(':', $field);
      }
      elseif (empty($multicolumn_field)) {
        throw new \RuntimeException('Field name missing for ' . $field);
      }
      else {
        $multicolumn_column = substr($field, 1);
      }

      $is_multicolumn = $multicolumn_field !== '' && $multicolumn_column !== '';
      $field_name = $multicolumn_field !== '' ? $multicolumn_field : $field;

      if ($this->fieldClassifier->fieldIsConfigurable($this->entityType, $field_name)) {
        try {
          $records = $this->parseCell((string) $field_value, $is_multicolumn);
        }
        catch (ParseException $e) {
          $errors[] = $e;
          continue;
        }

        if ($is_multicolumn) {
          foreach ($records as $key => $columns) {
            $multicolumn_fields[$multicolumn_field][$key][$multicolumn_column] = $columns;
          }
        }
        elseif ($field_value === '' || $field_value === NULL) {
          unset($parsed[$field_name]);
        }
        else {
          $parsed[$field_name] = $records;
        }
      }
      else {
        $is_base_field = $this->fieldClassifier->fieldIsBaseStandard($this->entityType, $field_name)
          || $this->fieldClassifier->fieldIsBaseComputedReadOnly($this->entityType, $field_name)
          || $this->fieldClassifier->fieldIsBaseComputedWritable($this->entityType, $field_name)
          || $this->fieldClassifier->fieldIsBaseCustomStorage($this->entityType, $field_name);

        if (!$is_base_field && !in_array($field_name, $this->ignoredProperties, TRUE)) {
          throw new \RuntimeException(sprintf('Field "%s" does not exist on entity type "%s".', $field_name, $this->entityType));
        }

        $parsed[$field] = $field_value;
      }
    }

    foreach ($multicolumn_fields as $field_name => $columns) {
      $parsed[$field_name] = $columns;
    }

    if (count($errors) === 1) {
      throw $errors[0];
    }

    if (count($errors) > 1) {
      throw new MultipleParseException($errors, $errors[0]->cell);
    }

    return $parsed;
  }

  /**
   * Parses a single cell value into a list of records.
   *
   * @return array<int, mixed>
   *   The parsed records.
   *
   * @throws \Drupal\DrupalExtension\Parser\Exception\ParseException
   *   On any cell-level parse error.
   */
  protected function parseCell(string $cell, bool $is_multicolumn): array {
    $trimmed = trim($cell);

    if ($trimmed === '') {
      return [''];
    }

    if (!$is_multicolumn && $this->detectCompoundMode($trimmed)) {
      return $this->parseCompound($trimmed);
    }

    return $this->parseScalarList($trimmed);
  }

  /**
   * Returns TRUE when the cell is in compound mode.
   *
   * Compound mode is detected by the presence of a top-level
   * 'key:"...' or 'key:[...]' pattern - i.e. an identifier, optional
   * whitespace, ':', optional whitespace, then '"' or '[' - with the
   * scan respecting quoted strings and bracketed tokens so an embedded
   * pattern inside a quoted scalar does not trigger compound mode.
   */
  protected function detectCompoundMode(string $cell): bool {
    $length = strlen($cell);
    $i = 0;

    while ($i < $length) {
      $char = $cell[$i];

      if ($char === '"') {
        $i = $this->skipQuotedString($cell, $i);
        continue;
      }

      if ($char === '[') {
        $i = $this->skipToken($cell, $i);
        continue;
      }

      if (preg_match('/[a-z_][a-z0-9_]*/A', $cell, $match, 0, $i) === 1) {
        $j = $i + strlen($match[0]);

        while ($j < $length && ($cell[$j] === ' ' || $cell[$j] === "\t")) {
          $j++;
        }

        if ($j < $length && $cell[$j] === ':') {
          $j++;

          while ($j < $length && ($cell[$j] === ' ' || $cell[$j] === "\t")) {
            $j++;
          }

          if ($j < $length && ($cell[$j] === '"' || $cell[$j] === '[')) {
            return TRUE;
          }
        }

        $i += strlen($match[0]);
        continue;
      }

      $i++;
    }

    return FALSE;
  }

  /**
   * Parses a scalar list (comma-separated, optional double-quote escape).
   *
   * @return array<int, string>
   *   The parsed list of scalar items.
   */
  protected function parseScalarList(string $cell): array {
    $items = [];
    $length = strlen($cell);
    $i = 0;
    $errors = [];

    while ($i <= $length) {
      while ($i < $length && ($cell[$i] === ' ' || $cell[$i] === "\t")) {
        $i++;
      }

      if ($cell[$i] === '"') {
        $start = $i;

        try {
          $value = $this->readQuotedString($cell, $i);
          $items[] = $value;
        }
        catch (ParseException $e) {
          $errors[] = $e;
          $i = $length;
          break;
        }

        while ($i < $length && ($cell[$i] === ' ' || $cell[$i] === "\t")) {
          $i++;
        }

        if ($i < $length && $cell[$i] !== ',') {
          $errors[] = new ParseException(
            'unexpected_character',
            $i,
            $cell,
            sprintf('Expected "," or end of value after closing quote, found "%s".', $cell[$i]),
            'Quote the entire scalar item or remove the stray character.',
          );
          $i = $length;
          break;
        }
      }
      else {
        $start = $i;

        while ($i < $length && $cell[$i] !== ',' && $cell[$i] !== ';' && $cell[$i] !== '"') {
          $i++;
        }

        $raw = substr($cell, $start, $i - $start);
        $value = rtrim($raw, " \t");

        if ($i < $length && $cell[$i] === ';') {
          $errors[] = new ParseException(
            'unquoted_semicolon',
            $i,
            $cell,
            'Semicolons are not allowed in unquoted scalar values.',
            sprintf('Wrap the value in double quotes: "%s".', $raw),
          );
          $i = $length;
          break;
        }

        if ($i < $length && $cell[$i] === '"') {
          $errors[] = new ParseException(
            'unexpected_quote',
            $i,
            $cell,
            'Unexpected double quote inside an unquoted scalar value.',
            'Wrap the entire item in double quotes if it contains a literal quote, then escape inner quotes as \\".',
          );
          $i = $length;
          break;
        }

        $items[] = $value;
      }

      if ($i >= $length) {
        break;
      }

      // $cell[$i] is now ','
      $i++;

      if ($i >= $length) {
        $items[] = '';
        break;
      }
    }

    if ($errors !== []) {
      throw $errors[0];
    }

    return $items;
  }

  /**
   * Parses a compound value: records separated by ';', columns by ','.
   *
   * @return array<int, array<string, string>>
   *   The parsed list of compound records.
   */
  protected function parseCompound(string $cell): array {
    $records = [];
    $errors = [];
    $record_strings = $this->splitTopLevel($cell, ';');

    foreach ($record_strings as $record) {
      $record = trim($record);

      if ($record === '') {
        $errors[] = new ParseException('empty_record', 0, $cell, 'Empty compound record (consecutive ";" or trailing ";").');

        continue;
      }

      try {
        $columns = $this->parseRecord($record, $cell);
        $records[] = $columns;
      }
      catch (ParseException $e) {
        $errors[] = $e;
      }
    }

    if ($errors !== []) {
      throw count($errors) === 1 ? $errors[0] : new MultipleParseException($errors, $cell);
    }

    return $records;
  }

  /**
   * Parses one compound record (','-separated columns) into a key/value map.
   *
   * @return array<string, string>
   *   The parsed columns keyed by column name.
   */
  protected function parseRecord(string $record, string $cell): array {
    $columns = [];
    $errors = [];
    $column_strings = $this->splitTopLevel($record, ',');

    foreach ($column_strings as $column) {
      $column = trim($column);

      if ($column === '') {
        $errors[] = new ParseException('empty_column', 0, $cell, 'Empty compound column (consecutive "," or trailing ",").');

        continue;
      }

      try {
        [$key, $value] = $this->parseColumn($column, $cell);
        $columns[$key] = $value;
      }
      catch (ParseException $e) {
        $errors[] = $e;
      }
    }

    if ($errors !== []) {
      throw count($errors) === 1 ? $errors[0] : new MultipleParseException($errors, $cell);
    }

    return $columns;
  }

  /**
   * Parses one 'key: value' column.
   *
   * @return array{0: string, 1: string}
   *   [$key, $value]
   */
  protected function parseColumn(string $column, string $cell): array {
    if (preg_match('/^([a-z_][a-z0-9_]*)\s*:\s*(.*)$/s', $column, $match) !== 1) {
      throw new ParseException(
        'invalid_column',
        0,
        $cell,
        sprintf('Compound column "%s" is not in "key: value" form.', $column),
        'Each compound column must look like "key: \"value\"" or "key: [token:value]".',
      );
    }

    $key = $match[1];
    $value_raw = trim($match[2]);

    if ($value_raw === '') {
      throw new ParseException(
        'unquoted_compound_value',
        0,
        $cell,
        sprintf('Compound column "%s" has no value.', $key),
        'Add a quoted string ("...") or a token ([name:value]).',
      );
    }

    if ($value_raw[0] === '"') {
      $offset = 0;
      $value = $this->readQuotedString($value_raw, $offset);

      if ($offset !== strlen($value_raw)) {
        throw new ParseException('trailing_characters', 0, $cell, sprintf('Trailing characters after closing quote in column "%s".', $key));
      }

      return [$key, $value];
    }

    if ($value_raw[0] === '[') {
      $offset = 0;
      $value = $this->readToken($value_raw, $offset);

      if ($offset !== strlen($value_raw)) {
        throw new ParseException('trailing_characters', 0, $cell, sprintf('Trailing characters after closing token in column "%s".', $key));
      }

      return [$key, $value];
    }

    throw new ParseException(
      'unquoted_compound_value',
      0,
      $cell,
      sprintf('Compound column "%s" must use a quoted string or token.', $key),
      'Wrap the value in double quotes or use a [token:value] form.',
    );
  }

  /**
   * Splits a string on a top-level separator character.
   *
   * Skips matches inside '"..."' or '[...]'.
   *
   * @return string[]
   *   The string segments separated by the top-level separator.
   */
  protected function splitTopLevel(string $cell, string $separator): array {
    $segments = [];
    $length = strlen($cell);
    $i = 0;
    $start = 0;

    while ($i < $length) {
      $char = $cell[$i];

      if ($char === '"') {
        $i = $this->skipQuotedString($cell, $i);
        continue;
      }

      if ($char === '[') {
        $i = $this->skipToken($cell, $i);
        continue;
      }

      if ($char === $separator) {
        $segments[] = substr($cell, $start, $i - $start);
        $i++;
        $start = $i;
        continue;
      }

      $i++;
    }

    $segments[] = substr($cell, $start);

    return $segments;
  }

  /**
   * Advances past a '"..."' quoted string, returning the index after the close.
   *
   * Matches the same escape grammar as 'readQuotedString()'. If the string
   * is unterminated returns the cell length.
   */
  protected function skipQuotedString(string $cell, int $i): int {
    $length = strlen($cell);
    $i++;

    while ($i < $length) {
      if ($cell[$i] === '\\' && $i + 1 < $length) {
        $i += 2;
        continue;
      }

      if ($cell[$i] === '"') {
        return $i + 1;
      }

      $i++;
    }

    return $length;
  }

  /**
   * Advances past a '[...]' token, returning the index after the close.
   *
   * If the token is unterminated returns the cell length.
   */
  protected function skipToken(string $cell, int $i): int {
    $length = strlen($cell);
    $close = strpos($cell, ']', $i + 1);

    if ($close === FALSE) {
      return $length;
    }

    return $close + 1;
  }

  /**
   * Reads a '"..."' quoted string starting at the current offset.
   *
   * Advances $offset past the closing quote. Decodes the escapes \\, \",
   * \n, \t, \r. Any other backslash sequence is a parse error.
   */
  protected function readQuotedString(string $cell, int &$offset): string {
    $length = strlen($cell);
    $start = $offset;
    $offset++;
    $out = '';

    while ($offset < $length) {
      $char = $cell[$offset];

      if ($char === '\\') {
        if ($offset + 1 >= $length) {
          throw new ParseException('unclosed_quote', $start, $cell, 'Quoted string ends with a dangling backslash.');
        }

        $next = $cell[$offset + 1];
        $decoded = match ($next) {
          '"' => '"',
          '\\' => '\\',
          'n' => "\n",
          't' => "\t",
          'r' => "\r",
          default => NULL,
        };

        if ($decoded === NULL) {
          throw new ParseException(
            'unknown_escape',
            $offset,
            $cell,
            sprintf('Unknown escape sequence "\\%s".', $next),
            'Supported escapes are \\", \\\\, \\n, \\t, \\r.',
          );
        }

        $out .= $decoded;
        $offset += 2;

        continue;
      }

      if ($char === '"') {
        $offset++;

        return $out;
      }

      $out .= $char;
      $offset++;
    }

    throw new ParseException(
      'unclosed_quote',
      $start,
      $cell,
      'Quoted string is missing a closing double quote.',
      'Add a closing double quote, or escape any inner quotes with \\".',
    );
  }

  /**
   * Reads a '[name:value]' token starting at the current offset.
   *
   * Advances $offset past the closing bracket and returns the verbatim
   * '[...]' substring (downstream field handlers expand the token).
   */
  protected function readToken(string $cell, int &$offset): string {
    $start = $offset;
    $close = strpos($cell, ']', $offset + 1);

    if ($close === FALSE) {
      throw new ParseException('unclosed_token', $start, $cell, 'Token is missing a closing "]".');
    }

    $offset = $close + 1;

    return substr($cell, $start, $offset - $start);
  }

}
