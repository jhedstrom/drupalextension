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
   * Reflection method for getSelector.
   */
  protected \ReflectionMethod $getSelector;

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
    $this->getSelector = new \ReflectionMethod(MessageContext::class, 'getSelector');
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
  #[DataProvider('dataProviderValidMessageTableAcceptsAllTypes')]
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
  public static function dataProviderValidMessageTableAcceptsAllTypes(): \Iterator {
    yield 'error messages' => ['Error messages', 'error messages'];
    yield 'success messages' => ['Success messages', 'success messages'];
    yield 'warning messages' => ['Warning messages', 'warning messages'];
  }

  /**
   * Tests that getSelector() resolves from the nested messages map.
   */
  #[DataProvider('dataProviderGetSelectorResolvesNestedSelector')]
  public function testGetSelectorResolvesNestedSelector(string $name, string $expected): void {
    $this->context->setParameters([
      'selectors' => [
        'messages' => [
          'default' => '.messages',
          'error' => '.messages--error',
          'success' => '.messages--status',
          'warning' => '.messages--warning',
        ],
      ],
    ]);
    $this->assertSame($expected, $this->getSelector->invoke($this->context, $name));
  }

  /**
   * Provides data for testGetSelectorResolvesNestedSelector().
   */
  public static function dataProviderGetSelectorResolvesNestedSelector(): \Iterator {
    yield 'default' => ['default', '.messages'];
    yield 'error' => ['error', '.messages--error'];
    yield 'success' => ['success', '.messages--status'];
    yield 'warning' => ['warning', '.messages--warning'];
  }

  /**
   * Tests that getSelector() throws when the message type is not configured.
   */
  #[DataProvider('dataProviderGetSelectorThrowsWhenNotConfigured')]
  public function testGetSelectorThrowsWhenNotConfigured(array $parameters): void {
    $this->context->setParameters($parameters);
    $this->expectException(\RuntimeException::class);
    $this->expectExceptionMessage('No CSS selector configured for the "error" message type. Define it under "Drupal\DrupalExtension.selectors.messages.error".');
    $this->getSelector->invoke($this->context, 'error');
  }

  /**
   * Provides data for testGetSelectorThrowsWhenNotConfigured().
   */
  public static function dataProviderGetSelectorThrowsWhenNotConfigured(): \Iterator {
    yield 'no selectors parameter' => [[]];
    yield 'selectors without a messages map' => [['selectors' => ['login_form_selector' => 'form#user-login']]];
    yield 'messages map missing the requested type' => [['selectors' => ['messages' => ['default' => '.messages']]]];
  }

}
