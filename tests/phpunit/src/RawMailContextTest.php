<?php

declare(strict_types=1);

namespace Drupal\DrupalExtension\Tests;

use Behat\Mink\Driver\DriverInterface as MinkDriverInterface;
use Behat\Mink\Mink;
use Behat\Mink\Session;
use Drupal\DrupalExtension\Context\RawMailContext;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Tests for RawMailContext.
 */
#[CoversClass(RawMailContext::class)]
class RawMailContextTest extends TestCase {

  /**
   * The RawMailContext instance under test.
   */
  protected RawMailContext $context;

  /**
   * Reflection method for matchMessage().
   */
  protected \ReflectionMethod $matchMessage;

  /**
   * Reflection method for sortMessages().
   */
  protected \ReflectionMethod $sortMessages;

  /**
   * Reflection method for compareMessages().
   */
  protected \ReflectionMethod $compareMessages;

  /**
   * Reflection method for assertMessageCount().
   */
  protected \ReflectionMethod $assertMessageCount;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    $this->context = new RawMailContext();

    $session = $this->createMock(Session::class);
    $session->method('getDriver')->willReturn($this->createMock(MinkDriverInterface::class));
    $mink = new Mink(['default' => $session]);
    $mink->setDefaultSessionName('default');
    $this->context->setMink($mink);

    $this->matchMessage = new \ReflectionMethod(RawMailContext::class, 'matchMessage');
    $this->sortMessages = new \ReflectionMethod(RawMailContext::class, 'sortMessages');
    $this->compareMessages = new \ReflectionMethod(RawMailContext::class, 'compareMessages');
    $this->assertMessageCount = new \ReflectionMethod(RawMailContext::class, 'assertMessageCount');
  }

  /**
   * Tests that matchMessage() returns expected results for various criteria.
   *
   * @param bool $expected
   *   The expected match result.
   * @param array<string, mixed> $message
   *   The mail message data.
   * @param array<string, mixed> $criteria
   *   The criteria to match against.
   */
  #[DataProvider('dataProviderMatchMessage')]
  public function testMatchMessage(bool $expected, array $message, array $criteria): void {
    $result = $this->matchMessage->invoke($this->context, $message, $criteria);
    $this->assertSame($expected, $result);
  }

  /**
   * Data provider for testMatchMessage().
   */
  public static function dataProviderMatchMessage(): \Iterator {
    yield 'empty criteria matches anything' => [TRUE, ['to' => 'alice@example.com', 'subject' => 'hello'], []];
    yield 'matching to field' => [
      TRUE, ['to' => 'alice@example.com', 'subject' => 'hello'], ['to' => 'alice@example.com'],
    ];
    yield 'case insensitive match' => [
      TRUE,
      ['to' => 'Alice@Example.COM', 'subject' => 'Hello World'],
      ['to' => 'alice@example.com', 'subject' => 'hello world'],
    ];
    yield 'substring match' => [
      TRUE, ['to' => 'alice@example.com', 'body' => 'The quick brown fox'], ['body' => 'quick brown'],
    ];
    yield 'non-matching field' => [
      FALSE, ['to' => 'alice@example.com', 'subject' => 'hello'], ['to' => 'bob@example.com'],
    ];
    yield 'empty string criteria ignored' => [
      TRUE,
      ['to' => 'alice@example.com', 'subject' => 'test'],
      ['to' => '', 'subject' => 'test'],
    ];
    yield 'all empty string criteria matches anything' => [
      TRUE, ['to' => 'alice@example.com'], ['to' => '', 'subject' => ''],
    ];
    yield 'multiple criteria all must match' => [
      FALSE,
      ['to' => 'alice@example.com', 'subject' => 'hello'],
      ['to' => 'alice@example.com', 'subject' => 'goodbye'],
    ];
  }

  /**
   * Tests that sortMessages() correctly sorts messages.
   *
   * @param array<int, array<string, string>> $input
   *   The input mail messages.
   * @param string $first_key
   *   First message expected key to assert against.
   * @param string $first_value
   *   First message expected value.
   * @param string $second_key
   *   Second message expected key to assert against.
   * @param string $second_value
   *   Second message expected value.
   */
  #[DataProvider('dataProviderSortMessages')]
  public function testSortMessages(array $input, string $first_key, string $first_value, string $second_key, string $second_value): void {
    $result = $this->sortMessages->invoke($this->context, $input);
    $this->assertSame($first_value, $result[0][$first_key]);
    $this->assertSame($second_value, $result[1][$second_key]);
  }

  /**
   * Data provider for testSortMessages().
   */
  public static function dataProviderSortMessages(): \Iterator {
    yield 'sort by to' => [
      [
        ['to' => 'bob@example.com', 'subject' => 'a', 'body' => 'x'],
        ['to' => 'alice@example.com', 'subject' => 'a', 'body' => 'x'],
      ],
      'to', 'alice@example.com', 'to', 'bob@example.com',
    ];
    yield 'sort by subject when to equal' => [
      [
        ['to' => 'a@b.com', 'subject' => 'zebra', 'body' => 'x'],
        ['to' => 'a@b.com', 'subject' => 'alpha', 'body' => 'x'],
      ],
      'subject', 'alpha', 'subject', 'zebra',
    ];
    yield 'sort by body when to and subject equal' => [
      [
        ['to' => 'a@b.com', 'subject' => 'x', 'body' => 'z'],
        ['to' => 'a@b.com', 'subject' => 'x', 'body' => 'a'],
      ],
      'body', 'a', 'body', 'z',
    ];
  }

  /**
   * Tests that sortMessages() handles an empty array.
   */
  public function testSortMessagesEmptyArray(): void {
    $this->assertSame([], $this->sortMessages->invoke($this->context, []));
  }

  /**
   * Tests that sortMessages() fills in missing keys on messages.
   */
  public function testSortMessagesFillsMissingKeys(): void {
    $result = $this->sortMessages->invoke($this->context, [['to' => 'a@b.com'], ['subject' => 'hello']]);
    foreach ($result as $message) {
      $this->assertArrayHasKey('to', $message);
      $this->assertArrayHasKey('subject', $message);
      $this->assertArrayHasKey('body', $message);
    }
  }

  /**
   * Tests that assertMessageCount() behaves as expected.
   *
   * @param array<int, array<string, string>> $messages
   *   The mail messages to count.
   * @param int|null $expected
   *   Expected count.
   * @param bool $should_throw
   *   Whether the assertion should throw.
   * @param string $exception_message
   *   Expected exception message.
   */
  #[DataProvider('dataProviderAssertMessageCount')]
  public function testAssertMessageCount(array $messages, ?int $expected, bool $should_throw, string $exception_message = ''): void {
    if ($should_throw) {
      $this->expectException(\Exception::class);
      $this->expectExceptionMessage($exception_message);
    }
    $this->assertMessageCount->invoke($this->context, $messages, $expected);
    if (!$should_throw) {
      $this->addToAssertionCount(1);
    }
  }

  /**
   * Data provider for testAssertMessageCount().
   */
  public static function dataProviderAssertMessageCount(): \Iterator {
    yield 'null expects at least one passes' => [[['to' => 'a']], NULL, FALSE];
    yield 'null throws on empty' => [[], NULL, TRUE, 'Expected some mail, but none found.'];
    yield 'exact count passes' => [[['to' => 'a', 'subject' => 'b'], ['to' => 'c', 'subject' => 'd']], 2, FALSE];
    yield 'count mismatch throws' => [[['to' => 'a', 'subject' => 'b']], 3, TRUE, 'Expected 3 mail, but 1 found:'];
    yield 'zero expected passes on empty' => [[], 0, FALSE];
  }

  /**
   * Tests that compareMessages() compares messages as expected.
   *
   * @param array<int, array<string, string>> $actual
   *   Actual messages.
   * @param array<int, array<string, string>> $expected
   *   Expected messages.
   * @param bool $should_throw
   *   Whether the assertion should throw.
   * @param string $exception_message
   *   Expected exception message.
   */
  #[DataProvider('dataProviderCompareMessages')]
  public function testCompareMessages(array $actual, array $expected, bool $should_throw, string $exception_message = ''): void {
    if ($should_throw) {
      $this->expectException(\Exception::class);
      $this->expectExceptionMessage($exception_message);
    }
    $this->compareMessages->invoke($this->context, $actual, $expected);
    if (!$should_throw) {
      $this->addToAssertionCount(1);
    }
  }

  /**
   * Data provider for testCompareMessages().
   */
  public static function dataProviderCompareMessages(): \Iterator {
    yield 'matching fields' => [
      [['to' => 'alice@example.com', 'subject' => 'hello', 'body' => 'world']],
      [['subject' => 'hello']],
      FALSE,
    ];
    yield 'field mismatch' => [
      [['to' => 'alice@example.com', 'subject' => 'hello', 'body' => 'world']],
      [['subject' => 'goodbye']],
      TRUE,
      "did not have 'goodbye' in its subject field",
    ];
    yield 'count mismatch' => [
      [['to' => 'a', 'subject' => 'b', 'body' => 'c']],
      [['subject' => 'b'], ['subject' => 'd']],
      TRUE,
      'Expected 2 mail, but 1 found:',
    ];
    yield 'sorts before comparing' => [
      [
        ['to' => 'bob@example.com', 'subject' => 'second', 'body' => '2'],
        ['to' => 'alice@example.com', 'subject' => 'first', 'body' => '1'],
      ],
      [['to' => 'alice'], ['to' => 'bob']],
      FALSE,
    ];
  }

}
