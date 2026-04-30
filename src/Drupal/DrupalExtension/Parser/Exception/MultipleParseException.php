<?php

declare(strict_types=1);

namespace Drupal\DrupalExtension\Parser\Exception;

/**
 * Container for multiple parse errors detected in a single cell.
 *
 * Parsers collect all errors detected in one cell before throwing, so the
 * test author sees every problem at once instead of fixing one, re-running,
 * and discovering the next.
 */
class MultipleParseException extends ParseException {

  /**
   * Wraps multiple parse errors detected in a single cell.
   *
   * @param ParseException[] $errors
   *   The individual parse errors. Must contain at least one entry.
   * @param string $cell
   *   The cell value being parsed when the errors were collected.
   * @param \Throwable|null $previous
   *   Optional previous throwable for chaining.
   */
  public function __construct(public readonly array $errors, string $cell, ?\Throwable $previous = NULL) {
    if ($errors === []) {
      throw new \InvalidArgumentException('MultipleParseException requires at least one error.');
    }

    $first = $errors[0];

    parent::__construct($first->errorCode, $first->offset, $cell, $this->buildDescription($errors), NULL, $previous);
  }

  /**
   * Builds a single-line description summarising the wrapped errors.
   *
   * @param ParseException[] $errors
   *   The wrapped errors.
   */
  protected function buildDescription(array $errors): string {
    $count = count($errors);

    if ($count === 1) {
      return $errors[0]->description;
    }

    $codes = array_map(fn(ParseException $error): string => $error->errorCode, $errors);

    return sprintf('%d parse errors: %s', $count, implode(', ', $codes));
  }

}
