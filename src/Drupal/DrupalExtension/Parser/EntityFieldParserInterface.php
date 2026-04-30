<?php

declare(strict_types=1);

namespace Drupal\DrupalExtension\Parser;

/**
 * Contract for entity-field value parsers.
 *
 * Implementations transform a raw map of field-name to cell-text pairs (as
 * returned by 'EntityStubInterface::getValues()') into a final map suitable
 * for handing back to 'EntityStubInterface::setValues()'. Each implementation
 * owns all syntactic concerns (CSV multi-value splitting, compound column
 * splitting, inline named-column interpretation, 'field:column' / ':column'
 * multicolumn-header merging) and all field-type semantics (configurable vs
 * base vs ignored vs unknown).
 *
 * Heavier dependencies needed for those decisions (entity type, classifier)
 * are constructor-injected. Per-call configuration that may vary between
 * stubs (e.g. the list of ignored property names) is set via fluent setters
 * before 'parse()' is called.
 */
interface EntityFieldParserInterface {

  /**
   * Parses raw stub values into final stub values.
   *
   * @param array<string|int, mixed> $values
   *   The raw stub values from 'EntityStubInterface::getValues()'.
   *
   * @return array<string, mixed>
   *   Final stub values, ready for 'EntityStubInterface::setValues()'.
   *
   * @throws \RuntimeException
   *   On unknown fields or orphan ':column' continuations.
   */
  public function parse(array $values): array;

  /**
   * Sets property names accepted without field-type validation.
   *
   * Used by callers that put driver-level creation hints on the stub
   * (e.g. 'author', 'role', 'vocabulary_machine_name') which are not real
   * Drupal fields but are consumed by the driver's create methods.
   *
   * @param string[] $properties
   *   Property names to accept without validation.
   *
   * @return static
   *   The same instance, for fluent chaining.
   */
  public function ignoring(array $properties): static;

}
