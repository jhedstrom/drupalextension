<?php

declare(strict_types=1);

namespace Drupal\DrupalExtension\Tests;

use Drupal\Driver\DriverInterface;
use Drupal\DrupalDriverManagerInterface;
use Drupal\DrupalExtension\Context\RawDrupalContext;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(RawDrupalContext::class)]
class RawDrupalContextTest extends TestCase
{

    protected RawDrupalContext $context;

    protected function setUp(): void
    {
        $this->context = new RawDrupalContext();

        $driver = $this->createMock(DriverInterface::class);
        $driver->method('isField')->willReturn(true);

        $drupal = $this->createMock(DrupalDriverManagerInterface::class);
        $drupal->method('getDriver')->willReturn($driver);

        $this->context->setDrupal($drupal);
    }

    #[DataProvider('dataProviderParseEntityFieldsSimple')]
    public function testParseEntityFieldsSimple(string $input, array $expected): void
    {
        $entity = (object) ['field_test' => $input];
        $this->context->parseEntityFields('node', $entity);
        $this->assertSame($expected, $entity->field_test);
    }

    public static function dataProviderParseEntityFieldsSimple(): \Iterator
    {
        yield 'single value' => [
            'A',
            ['A'],
        ];
        yield 'multiple csv values' => [
            'A, B, C',
            ['A', 'B', 'C'],
        ];
        yield 'csv with quoted comma' => [
            'A, "a value, containing a comma"',
            ['A', 'a value, containing a comma'],
        ];
    }

    public function testParseEntityFieldsCompoundSeparator(): void
    {
        $entity = (object) ['field_test' => 'A - B'];
        $this->context->parseEntityFields('node', $entity);
        $this->assertSame([['A', 'B']], $entity->field_test);
    }

    public function testParseEntityFieldsInlineNamedColumns(): void
    {
        $entity = (object) ['field_test' => 'x: A - y: B'];
        $this->context->parseEntityFields('node', $entity);
        $this->assertSame([['x' => 'A', 'y' => 'B']], $entity->field_test);
    }

    public function testParseEntityFieldsMultiValueCompound(): void
    {
        $entity = (object) ['field_test' => 'A - B, C - D'];
        $this->context->parseEntityFields('node', $entity);
        $this->assertSame([['A', 'B'], ['C', 'D']], $entity->field_test);
    }

    public function testParseEntityFieldsMultiValueNamedColumns(): void
    {
        $entity = (object) ['field_test' => 'x: A - y: B, x: C - y: D'];
        $this->context->parseEntityFields('node', $entity);
        $this->assertSame(
            [['x' => 'A', 'y' => 'B'], ['x' => 'C', 'y' => 'D']],
            $entity->field_test
        );
    }

    public function testParseEntityFieldsBlankValueUnsets(): void
    {
        $entity = (object) ['field_test' => ''];
        $this->context->parseEntityFields('node', $entity);
        $this->assertObjectNotHasProperty('field_test', $entity);
    }

    public function testParseEntityFieldsMulticolumn(): void
    {
        $entity = (object) [
            'field_test:col_a' => 'value_a',
            ':col_b' => 'value_b',
        ];
        $this->context->parseEntityFields('node', $entity);
        $this->assertSame(
            [0 => ['col_a' => 'value_a', 'col_b' => 'value_b']],
            $entity->field_test
        );
    }

    public function testParseEntityFieldsMulticolumnMultipleValues(): void
    {
        $entity = (object) [
            'field_test:col_a' => 'A1, A2',
            ':col_b' => 'B1, B2',
        ];
        $this->context->parseEntityFields('node', $entity);
        $this->assertSame(
            [
                0 => ['col_a' => 'A1', 'col_b' => 'B1'],
                1 => ['col_a' => 'A2', 'col_b' => 'B2'],
            ],
            $entity->field_test
        );
    }

    public function testParseEntityFieldsOrphanedColumnThrows(): void
    {
        $entity = (object) [':orphan' => 'value'];
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Field name missing for :orphan');
        $this->context->parseEntityFields('node', $entity);
    }

    public function testParseEntityFieldsNonFieldUntouched(): void
    {
        $driver = $this->createMock(DriverInterface::class);
        $driver->method('isField')->willReturn(false);

        $drupal = $this->createMock(DrupalDriverManagerInterface::class);
        $drupal->method('getDriver')->willReturn($driver);

        $context = new RawDrupalContext();
        $context->setDrupal($drupal);

        $entity = (object) ['title' => 'Some title'];
        $context->parseEntityFields('node', $entity);
        $this->assertSame('Some title', $entity->title);
    }

    public function testParseEntityFieldsMulticolumnBlankValuePreserved(): void
    {
        $entity = (object) [
            'field_test:col_a' => '',
            ':col_b' => '',
        ];
        $this->context->parseEntityFields('node', $entity);
        $this->assertObjectHasProperty('field_test', $entity);
        $this->assertSame(
            [0 => ['col_a' => '', 'col_b' => '']],
            $entity->field_test
        );
    }
}
