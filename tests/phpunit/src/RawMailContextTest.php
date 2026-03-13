<?php

declare(strict_types=1);

namespace Drupal\DrupalExtension\Tests;

use Drupal\DrupalExtension\Context\RawMailContext;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(RawMailContext::class)]
class RawMailContextTest extends TestCase
{

    protected RawMailContext $context;

    protected \ReflectionMethod $matchMessage;

    protected \ReflectionMethod $sortMessages;

    protected \ReflectionMethod $compareMessages;

    protected \ReflectionMethod $assertMessageCount;

    protected function setUp(): void
    {
        $this->context = new RawMailContext();
        $this->matchMessage = new \ReflectionMethod(RawMailContext::class, 'matchMessage');
        $this->sortMessages = new \ReflectionMethod(RawMailContext::class, 'sortMessages');
        $this->compareMessages = new \ReflectionMethod(RawMailContext::class, 'compareMessages');
        $this->assertMessageCount = new \ReflectionMethod(RawMailContext::class, 'assertMessageCount');
    }

    #[DataProvider('dataProviderMatchMessage')]
    public function testMatchMessage(bool $expected, array $message, array $criteria): void
    {
        $result = $this->matchMessage->invoke($this->context, $message, $criteria);
        $this->assertSame($expected, $result);
    }

    public static function dataProviderMatchMessage(): \Iterator
    {
        yield 'empty criteria matches anything' => [true, ['to' => 'alice@example.com', 'subject' => 'hello'], []];
        yield 'matching to field' => [true, ['to' => 'alice@example.com', 'subject' => 'hello'], ['to' => 'alice@example.com']];
        yield 'case insensitive match' => [true, ['to' => 'Alice@Example.COM', 'subject' => 'Hello World'], ['to' => 'alice@example.com', 'subject' => 'hello world']];
        yield 'substring match' => [true, ['to' => 'alice@example.com', 'body' => 'The quick brown fox'], ['body' => 'quick brown']];
        yield 'non-matching field' => [false, ['to' => 'alice@example.com', 'subject' => 'hello'], ['to' => 'bob@example.com']];
        yield 'empty string criteria ignored' => [true, ['to' => 'alice@example.com', 'subject' => 'test'], ['to' => '', 'subject' => 'test']];
        yield 'all empty string criteria matches anything' => [true, ['to' => 'alice@example.com'], ['to' => '', 'subject' => '']];
        yield 'multiple criteria all must match' => [false, ['to' => 'alice@example.com', 'subject' => 'hello'], ['to' => 'alice@example.com', 'subject' => 'goodbye']];
    }

    #[DataProvider('dataProviderSortMessages')]
    public function testSortMessages(array $input, string $first_key, string $first_value, string $second_key, string $second_value): void
    {
        $result = $this->sortMessages->invoke($this->context, $input);
        $this->assertSame($first_value, $result[0][$first_key]);
        $this->assertSame($second_value, $result[1][$second_key]);
    }

    public static function dataProviderSortMessages(): \Iterator
    {
        yield 'sort by to' => [
            [['to' => 'bob@example.com', 'subject' => 'a', 'body' => 'x'], ['to' => 'alice@example.com', 'subject' => 'a', 'body' => 'x']],
            'to', 'alice@example.com', 'to', 'bob@example.com',
        ];
        yield 'sort by subject when to equal' => [
            [['to' => 'a@b.com', 'subject' => 'zebra', 'body' => 'x'], ['to' => 'a@b.com', 'subject' => 'alpha', 'body' => 'x']],
            'subject', 'alpha', 'subject', 'zebra',
        ];
        yield 'sort by body when to and subject equal' => [
            [['to' => 'a@b.com', 'subject' => 'x', 'body' => 'z'], ['to' => 'a@b.com', 'subject' => 'x', 'body' => 'a']],
            'body', 'a', 'body', 'z',
        ];
    }

    public function testSortMessagesEmptyArray(): void
    {
        $this->assertSame([], $this->sortMessages->invoke($this->context, []));
    }

    public function testSortMessagesFillsMissingKeys(): void
    {
        $result = $this->sortMessages->invoke($this->context, [['to' => 'a@b.com'], ['subject' => 'hello']]);
        foreach ($result as $message) {
            $this->assertArrayHasKey('to', $message);
            $this->assertArrayHasKey('subject', $message);
            $this->assertArrayHasKey('body', $message);
        }
    }

    #[DataProvider('dataProviderAssertMessageCount')]
    public function testAssertMessageCount(array $messages, ?int $expected, bool $should_throw, string $exception_message = ''): void
    {
        if ($should_throw) {
            $this->expectException(\Exception::class);
            $this->expectExceptionMessage($exception_message);
        }
        $this->assertMessageCount->invoke($this->context, $messages, $expected);
        if (!$should_throw) {
            $this->addToAssertionCount(1);
        }
    }

    public static function dataProviderAssertMessageCount(): \Iterator
    {
        yield 'null expects at least one passes' => [[['to' => 'a']], null, false];
        yield 'null throws on empty' => [[], null, true, 'Expected some mail, but none found.'];
        yield 'exact count passes' => [[['to' => 'a', 'subject' => 'b'], ['to' => 'c', 'subject' => 'd']], 2, false];
        yield 'count mismatch throws' => [[['to' => 'a', 'subject' => 'b']], 3, true, 'Expected 3 mail, but 1 found:'];
        yield 'zero expected passes on empty' => [[], 0, false];
    }

    #[DataProvider('dataProviderCompareMessages')]
    public function testCompareMessages(array $actual, array $expected, bool $should_throw, string $exception_message = ''): void
    {
        if ($should_throw) {
            $this->expectException(\Exception::class);
            $this->expectExceptionMessage($exception_message);
        }
        $this->compareMessages->invoke($this->context, $actual, $expected);
        if (!$should_throw) {
            $this->addToAssertionCount(1);
        }
    }

    public static function dataProviderCompareMessages(): \Iterator
    {
        yield 'matching fields' => [
            [['to' => 'alice@example.com', 'subject' => 'hello', 'body' => 'world']],
            [['subject' => 'hello']],
            false,
        ];
        yield 'field mismatch' => [
            [['to' => 'alice@example.com', 'subject' => 'hello', 'body' => 'world']],
            [['subject' => 'goodbye']],
            true,
            "did not have 'goodbye' in its subject field",
        ];
        yield 'count mismatch' => [
            [['to' => 'a', 'subject' => 'b', 'body' => 'c']],
            [['subject' => 'b'], ['subject' => 'd']],
            true,
            'Expected 2 mail, but 1 found:',
        ];
        yield 'sorts before comparing' => [
            [['to' => 'bob@example.com', 'subject' => 'second', 'body' => '2'], ['to' => 'alice@example.com', 'subject' => 'first', 'body' => '1']],
            [['to' => 'alice'], ['to' => 'bob']],
            false,
        ];
    }
}
