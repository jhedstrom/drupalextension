<?php

declare(strict_types=1);

namespace Drupal\DrupalExtension\Tests;

use Behat\Gherkin\Node\TableNode;
use Drupal\DrupalExtension\Context\MessageContext;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Tests the MessageContext class.
 */
#[CoversClass(MessageContext::class)]
class MessageContextTest extends TestCase {

  /**
   * Reflection method for assertValidMessageTable.
   */
  protected \ReflectionMethod $assertValidMessageTable;

  /**
   * The context under test.
   */
  protected MessageContext $context;

  /**
   * Sets up test fixtures.
   */
  protected function setUp(): void {
    $this->context = new MessageContext();
    $this->assertValidMessageTable = new \ReflectionMethod(MessageContext::class, 'assertValidMessageTable');
  }

  /**
   * Tests that a valid message table passes validation.
   */
  public function testValidMessageTablePasses(): void {
    $table = new TableNode([
          ['Error messages'],
          ['Something went wrong'],
    ]);
    $this->assertValidMessageTable->invoke($this->context, $table, 'error messages');
    $this->addToAssertionCount(1);
  }

  /**
   * Tests that message table validation is case insensitive.
   */
  public function testValidMessageTableCaseInsensitive(): void {
    $table = new TableNode([
          ['ERROR MESSAGES'],
          ['Something went wrong'],
    ]);
    $this->assertValidMessageTable->invoke($this->context, $table, 'error messages');
    $this->addToAssertionCount(1);
  }

  /**
   * Tests that invalid column count throws an exception.
   */
  public function testInvalidColumnCountThrows(): void {
    $table = new TableNode([
          ['Error messages', 'Extra column'],
          ['Something went wrong', 'extra'],
    ]);
    $this->expectException(\RuntimeException::class);
    $this->expectExceptionMessage('should only contain 1 column. It has 2 columns');
    $this->assertValidMessageTable->invoke($this->context, $table, 'error messages');
  }

  /**
   * Tests that an invalid header throws an exception.
   */
  public function testInvalidHeaderThrows(): void {
    $table = new TableNode([
          ['Wrong header'],
          ['Something went wrong'],
    ]);
    $this->expectException(\RuntimeException::class);
    $this->expectExceptionMessage("should have the header 'Error messages', but found 'Wrong header'");
    $this->assertValidMessageTable->invoke($this->context, $table, 'error messages');
  }

  /**
   * Tests that all message types are accepted.
   */
  #[DataProvider('dataProviderMessageTypes')]
  public function testValidMessageTableAcceptsAllTypes(string $header, string $type): void {
    $table = new TableNode([
          [$header],
          ['Test message'],
    ]);
    $this->assertValidMessageTable->invoke($this->context, $table, $type);
    $this->addToAssertionCount(1);
  }

  /**
   * Provides data for testValidMessageTableAcceptsAllTypes().
   */
  public static function dataProviderMessageTypes(): \Iterator {
    yield 'error messages' => ['Error messages', 'error messages'];
    yield 'success messages' => ['Success messages', 'success messages'];
    yield 'warning messages' => ['Warning messages', 'warning messages'];
  }

}
