<?php

declare(strict_types=1);

namespace Drupal\DrupalExtension\Parser\Exception;

/**
 * Single parse error with position-aware information.
 *
 * Carries:
 *   - A machine-readable error code (e.g. 'unclosed_quote').
 *   - The zero-based character offset within the parsed cell.
 *   - The cell text being parsed and a caret pointer underneath.
 *   - A human-readable message and an optional suggested fix.
 *
 * The exception message returned by 'getMessage()' is a multi-line string
 * containing the cell, a caret line, and a description; suitable for
 * surfacing directly in a Behat run.
 */
class ParseException extends \RuntimeException {

  public function __construct(
    public readonly string $errorCode,
    public readonly int $offset,
    public readonly string $cell,
    public readonly string $description,
    public readonly ?string $hint = NULL,
    ?\Throwable $previous = NULL,
  ) {
    parent::__construct($this->buildMessage(), 0, $previous);
  }

  /**
   * Builds the multi-line, human-readable exception message.
   */
  protected function buildMessage(): string {
    $caret = str_repeat(' ', max(0, $this->offset)) . '^';
    $lines = [$this->cell, $caret, sprintf('%s at offset %d: %s', $this->errorCode, $this->offset, $this->description)];

    if ($this->hint !== NULL && $this->hint !== '') {
      $lines[] = 'Hint: ' . $this->hint;
    }

    return implode("\n", $lines);
  }

}
