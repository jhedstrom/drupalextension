<?php

declare(strict_types=1);

namespace Drupal\DrupalExtension\Tests\Parser\Exception;

use Drupal\DrupalExtension\Parser\Exception\MultipleParseException;
use Drupal\DrupalExtension\Parser\Exception\ParseException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * Tests the parse-exception classes.
 */
#[CoversClass(ParseException::class)]
#[CoversClass(MultipleParseException::class)]
class ParseExceptionTest extends TestCase {

  /**
   * Tests that the exception message renders cell, caret, and description.
   */
  public function testParseExceptionMessageWithoutHint(): void {
    $exception = new ParseException(
      'unclosed_quote',
      4,
      'name:Alice',
      'Quoted string is missing a closing double quote.',
    );

    $this->assertSame('unclosed_quote', $exception->errorCode);
    $this->assertSame(4, $exception->offset);
    $this->assertSame('name:Alice', $exception->cell);

    $expected = "name:Alice\n    ^\nunclosed_quote at offset 4: Quoted string is missing a closing double quote.";
    $this->assertSame($expected, $exception->getMessage());
  }

  /**
   * Tests that an optional hint is appended to the message.
   */
  public function testParseExceptionMessageWithHint(): void {
    $exception = new ParseException(
      'unknown_escape',
      6,
      'a:"x\\xy"',
      'Unknown escape sequence "\\x".',
      'Supported escapes are \\", \\\\, \\n, \\t, \\r.',
    );

    $this->assertStringContainsString('Hint: Supported escapes are', $exception->getMessage());
  }

  /**
   * Tests that MultipleParseException requires at least one error.
   */
  public function testMultipleParseExceptionRequiresErrors(): void {
    $this->expectException(\InvalidArgumentException::class);
    $this->expectExceptionMessage('MultipleParseException requires at least one error.');

    new MultipleParseException([], 'cell');
  }

  /**
   * Tests that MultipleParseException collects multiple errors and summarises.
   */
  public function testMultipleParseExceptionAggregates(): void {
    $first = new ParseException('empty_record', 0, 'a:"x";; b:"y"', 'Empty compound record.');
    $second = new ParseException('empty_column', 0, 'a:"x";; b:"y"', 'Empty compound column.');

    $exception = new MultipleParseException([$first, $second], 'a:"x";; b:"y"');

    $this->assertSame('empty_record', $exception->errorCode);
    $this->assertSame(2, count($exception->errors));
    $this->assertStringContainsString('2 parse errors: empty_record, empty_column', $exception->getMessage());
  }

  /**
   * Tests that a single-error MultipleParseException keeps its description.
   */
  public function testMultipleParseExceptionWithSingleError(): void {
    $error = new ParseException('unclosed_quote', 0, 'cell', 'Description here.');

    $exception = new MultipleParseException([$error], 'cell');

    $this->assertStringContainsString('Description here.', $exception->getMessage());
  }

}
