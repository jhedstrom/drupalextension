<?php

declare(strict_types=1);

namespace Drupal\DrupalExtension\Parser;

use Drupal\Driver\Core\Field\FieldClassifierInterface;

/**
 * Legacy entity-field parser.
 *
 * Implements the pre-v6 inline syntax: ',' separates multi-value items,
 * ' - ' separates compound columns, ': ' separates inline 'key: value'
 * named columns, and 'field:column' / ':column' header rows merge into a
 * single canonical field entry. Frozen at the 5.x behaviour and scheduled
 * for removal in 6.1; do not extend.
 *
 * Single value:
 * @code
 * | title       | field_color |
 * | My article  | Red         |
 * @endcode
 * Result: field_color = ['Red'].
 *
 * Multiple values (comma-separated):
 * @code
 * | field_tags        |
 * | Sports, Politics  |
 * @endcode
 * Result: field_tags = ['Sports', 'Politics'].
 * Wrap in double quotes to include a literal comma: "a value, with comma".
 *
 * Compound columns using ' - ' separator (e.g. link field with uri + title):
 * @code
 * | field_link                        |
 * | http://example.com - Example site |
 * @endcode
 * Result: field_link = [['http://example.com', 'Example site']].
 *
 * Named compound columns using inline 'key: value' syntax:
 * @code
 * | field_link                                    |
 * | uri: http://example.com - title: Example site |
 * @endcode
 * Result: field_link = [
 *   ['uri' => 'http://example.com', 'title' => 'Example site']
 * ].
 *
 * Multi-value compound (comma separates each value set):
 * @code
 * | field_link                                                 |
 * | uri: /about - title: About, uri: /contact - title: Contact |
 * @endcode
 * Result: field_link = [
 *   ['uri' => '/about', 'title' => 'About'],
 *   ['uri' => '/contact', 'title' => 'Contact'],
 * ].
 *
 * Multicolumn table headers using 'field:column' and ':column' syntax
 * (useful when compound values contain commas or separators):
 * @code
 * | field_link:uri          | :title       |
 * | http://example.com      | Example site |
 * @endcode
 * Result: field_link = [
 *   ['uri' => 'http://example.com', 'title' => 'Example site']
 * ].
 *
 * Multi-value multicolumn (comma-separated within each cell):
 * @code
 * | field_link:uri          | :title              |
 * | /about, /contact        | About, Contact      |
 * @endcode
 * Result: field_link = [
 *   ['uri' => '/about', 'title' => 'About'],
 *   ['uri' => '/contact', 'title' => 'Contact'],
 * ].
 */
final class LegacyEntityFieldParser implements EntityFieldParserInterface {

  /**
   * Property names accepted without field-type validation.
   *
   * @var string[]
   */
  protected array $ignoredProperties = [];

  /**
   * Constructs the parser for one entity-type / bundle / classifier pairing.
   *
   * @param string $entityType
   *   The entity type ID.
   * @param \Drupal\Driver\Core\Field\FieldClassifierInterface $fieldClassifier
   *   The field classifier.
   * @param string|null $bundle
   *   The bundle for the stub being parsed, or NULL when the entity type
   *   has no bundles. When provided, bundle-scoped fields (F6-F9) are
   *   accepted as known fields.
   */
  public function __construct(
    protected readonly string $entityType,
    protected readonly FieldClassifierInterface $fieldClassifier,
    protected readonly ?string $bundle = null,
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

    foreach ($values as $field => $field_value) {
      $field = (string) $field;

      // Reset the multicolumn field if the field name does not have a column.
      if (!str_contains($field, ':')) {
        $multicolumn_field = '';
        $multicolumn_column = '';
      }
      elseif (str_contains(substr($field, 1), ':')) {
        // Start tracking a new multicolumn field if the field name contains a
        // ':' which is preceded by at least 1 character.
        [$multicolumn_field, $multicolumn_column] = explode(':', $field);
      }
      elseif (empty($multicolumn_field)) {
        // If a field name starts with a ':' but we are not yet tracking a
        // multicolumn field we don't know to which field this belongs.
        throw new \RuntimeException('Field name missing for ' . $field);
      }
      else {
        // Update the column name if the field name starts with a ':' and we
        // are already tracking a multicolumn field.
        $multicolumn_column = substr($field, 1);
      }

      $is_multicolumn = $multicolumn_field !== '' && $multicolumn_column !== '';
      $field_name = $multicolumn_field !== '' ? $multicolumn_field : $field;

      if ($this->fieldClassifier->fieldIsConfigurable($this->entityType, $field_name)) {
        // Split up multiple values in multi-value fields.
        $parsed_values = [];

        foreach (str_getcsv((string) $field_value, escape: "\\") as $key => $value) {
          $value = trim((string) $value);
          $columns = $value;
          // Skip splitting if the value was double-quoted in the original
          // field value, allowing values like "Alpha - Bravo" to pass through
          // as-is (e.g. entity reference titles with dashes).
          // @see https://github.com/jhedstrom/drupalextension/issues/642
          $was_quoted = str_contains((string) $field_value, '"' . $value . '"');

          if (!$was_quoted && str_contains($value, ' - ')) {
            $columns = [];

            foreach (explode(' - ', $value) as $column) {
              if (!$is_multicolumn && str_contains(substr($column, 1), ': ')) {
                [$inline_key, $column] = explode(': ', $column);
                $columns[$inline_key] = $column;
              }
              else {
                $columns[] = $column;
              }
            }
          }

          if ($is_multicolumn) {
            $multicolumn_fields[$multicolumn_field][$key][$multicolumn_column] = $columns;
          }
          else {
            $parsed_values[] = $columns;
          }
        }

        if (!$is_multicolumn) {
          // Don't specify any value if the step author has left it blank.
          if ($field_value === '' || $field_value === NULL) {
            unset($parsed[$field_name]);
          }
          else {
            $parsed[$field_name] = $parsed_values;
          }
        }
      }
      else {
        // The classifier splits base fields across F1-F4 (standard, computed
        // read-only, computed writable, custom storage). All four predicates
        // must be checked so computed and custom-storage base fields like
        // 'moderation_state' do not trip the unknown-field guard. When the
        // bundle is known, also accept F6-F9 (bundle-scoped fields) so that
        // fields contributed via 'hook_entity_bundle_field_info()' such as
        // rdf_sync's 'uri' are recognised.
        $is_known = $this->fieldClassifier->fieldIsBaseStandard($this->entityType, $field_name)
          || $this->fieldClassifier->fieldIsBaseComputedReadOnly($this->entityType, $field_name)
          || $this->fieldClassifier->fieldIsBaseComputedWritable($this->entityType, $field_name)
          || $this->fieldClassifier->fieldIsBaseCustomStorage($this->entityType, $field_name)
          || (
            $this->bundle !== null
            && (
              $this->fieldClassifier->fieldIsBundleComputedReadOnly($this->entityType, $field_name, $this->bundle)
              || $this->fieldClassifier->fieldIsBundleComputedWritable($this->entityType, $field_name, $this->bundle)
              || $this->fieldClassifier->fieldIsBundleCustomStorage($this->entityType, $field_name, $this->bundle)
              || $this->fieldClassifier->fieldIsBundleStorageBacked($this->entityType, $field_name, $this->bundle)
            )
          );

        if (!$is_known && !in_array($field_name, $this->ignoredProperties, TRUE)) {
          throw new \RuntimeException(sprintf('Field "%s" does not exist on entity type "%s".', $field_name, $this->entityType));
        }

        $parsed[$field] = $field_value;
      }
    }

    foreach ($multicolumn_fields as $field_name => $columns) {
      $parsed[$field_name] = $columns;
    }

    return $parsed;
  }

}
