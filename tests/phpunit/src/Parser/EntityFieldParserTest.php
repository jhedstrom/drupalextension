<?php

declare(strict_types=1);

namespace Drupal\DrupalExtension\Tests\Parser;

use Drupal\Driver\Core\Field\FieldClassifierInterface;
use Drupal\DrupalExtension\Parser\EntityFieldParser;
use Drupal\DrupalExtension\Parser\Exception\MultipleParseException;
use Drupal\DrupalExtension\Parser\Exception\ParseException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Tests the modern entity-field parser.
 */
#[CoversClass(EntityFieldParser::class)]
class EntityFieldParserTest extends TestCase {

  /**
   * Tests parsing across the full happy-path and error matrix.
   *
   * @param array<string|int, mixed> $input
   *   Raw stub values to feed the parser.
   * @param array<string, mixed> $expected
   *   The expected parsed output (ignored when an exception is expected).
   * @param array<int, string>|null $configurable_fields
   *   Field names the classifier should report as configurable, or NULL
   *   to treat every field as configurable (the default).
   * @param array<int, string>|null $base_fields
   *   Field names the classifier should report as base fields.
   * @param class-string<\Throwable>|null $exception
   *   Expected exception class, or NULL when no exception is expected.
   * @param string|null $error_code
   *   Expected ParseException::$errorCode, when applicable.
   * @param string|null $message
   *   Expected exception message substring, when applicable.
   * @param array<int, string> $ignored_properties
   *   Property names passed to the parser via ignoring().
   */
  #[DataProvider('dataProviderParse')]
  public function testParse(
    array $input,
    array $expected = [],
    ?array $configurable_fields = NULL,
    ?array $base_fields = NULL,
    ?string $exception = NULL,
    ?string $error_code = NULL,
    ?string $message = NULL,
    array $ignored_properties = [],
  ): void {
    $classifier = $this->createMock(FieldClassifierInterface::class);

    if ($configurable_fields === NULL) {
      $classifier->method('fieldIsConfigurable')->willReturn(TRUE);
    }
    else {
      $classifier->method('fieldIsConfigurable')->willReturnCallback(
        fn(string $entity_type, string $field_name): bool => in_array($field_name, $configurable_fields, TRUE)
      );
      $is_base = fn(string $entity_type, string $field_name): bool => in_array($field_name, $base_fields ?? [], TRUE);
      $classifier->method('fieldIsBaseStandard')->willReturnCallback($is_base);
      $classifier->method('fieldIsBaseComputedReadOnly')->willReturnCallback($is_base);
      $classifier->method('fieldIsBaseComputedWritable')->willReturnCallback($is_base);
      $classifier->method('fieldIsBaseCustomStorage')->willReturnCallback($is_base);
    }

    $parser = (new EntityFieldParser('node', $classifier))->ignoring($ignored_properties);

    if ($exception !== NULL) {
      try {
        $parser->parse($input);
        $this->fail(sprintf('Expected %s, none thrown.', $exception));
      }
      catch (\RuntimeException $caught) {
        $this->assertInstanceOf($exception, $caught);

        if ($error_code !== NULL) {
          $this->assertInstanceOf(ParseException::class, $caught);
          $this->assertSame($error_code, $caught->errorCode);
        }

        if ($message !== NULL) {
          $this->assertStringContainsString($message, $caught->getMessage());
        }
      }

      return;
    }

    $this->assertSame($expected, $parser->parse($input));
  }

  /**
   * Tests that multi-error cells throw MultipleParseException with all errors.
   */
  public function testMultiErrorReportedTogether(): void {
    $classifier = $this->createMock(FieldClassifierInterface::class);
    $classifier->method('fieldIsConfigurable')->willReturn(TRUE);

    $parser = new EntityFieldParser('node', $classifier);

    try {
      $parser->parse(['field_test' => 'a:"x";; b:"y", c:bad']);
      $this->fail('Expected MultipleParseException, none thrown.');
    }
    catch (MultipleParseException $e) {
      $this->assertGreaterThanOrEqual(2, count($e->errors));
    }
  }

  /**
   * Provides data for testParse().
   *
   * Each yielded row mirrors a row of the syntax validity table in the
   * issue description for #766, so this provider reads as a top-to-bottom
   * encoding of that table.
   */
  public static function dataProviderParse(): \Iterator {
    // Row 1: scalar, single.
    yield '1. scalar, single' => [
      ['field_test' => 'Hello'],
      ['field_test' => ['Hello']],
    ];
    // Row 2: scalar, single, contains ":".
    yield '2. scalar, single, contains ":"' => [
      ['field_test' => 'Note: this is important'],
      ['field_test' => ['Note: this is important']],
    ];
    // Row 3: scalar, single, contains ",".
    yield '3. scalar, single, contains ","' => [
      ['field_test' => '"Hello, world"'],
      ['field_test' => ['Hello, world']],
    ];
    // Row 4: scalar, single, contains " - " (issue #642 resolved).
    yield '4. scalar, single, contains " - "' => [
      ['field_test' => 'Alpha - Bravo'],
      ['field_test' => ['Alpha - Bravo']],
    ];
    // Row 5: scalar, single, contains ";" (must be quoted now).
    yield '5. scalar, single, contains ";"' => [
      ['field_test' => '"Hello; world"'],
      ['field_test' => ['Hello; world']],
    ];
    // Row 6: scalar, looks like key:value but is not.
    yield '6. scalar, looks like key:value' => [
      ['field_test' => 'port:8080'],
      ['field_test' => ['port:8080']],
    ];
    // Row 7: scalar, multi-value.
    yield '7. scalar, multi-value' => [
      ['field_test' => 'Tag one, Tag two'],
      ['field_test' => ['Tag one', 'Tag two']],
    ];
    // Row 8: scalar, multi-value, item contains ",".
    yield '8. scalar, multi-value, item contains ","' => [
      ['field_test' => 'Tag one, "Tag, two"'],
      ['field_test' => ['Tag one', 'Tag, two']],
    ];
    // Row 9: token, single.
    yield '9. token, single' => [
      ['field_test' => '[relative:-1 week]'],
      ['field_test' => ['[relative:-1 week]']],
    ];
    // Row 10: token, multi.
    yield '10. token, multi' => [
      ['field_test' => '[relative:-1 week], [relative:-2 weeks]'],
      ['field_test' => ['[relative:-1 week]', '[relative:-2 weeks]']],
    ];
    // Row 11: token in scalar prose.
    yield '11. token in scalar prose' => [
      ['field_test' => 'Posted [relative:-1 week] ago'],
      ['field_test' => ['Posted [relative:-1 week] ago']],
    ];
    // Row 12: token at compound value position.
    yield '12. token at compound value position' => [
      ['field_test' => 'value:[relative:-1 week], end_value:[relative:+1 week]'],
      [
        'field_test' => [[
          'value' => '[relative:-1 week]',
          'end_value' => '[relative:+1 week]',
        ],
        ],
      ],
    ];
    // Row 13: compound named, single (text_with_summary).
    yield '13. compound named, single (text_with_summary)' => [
      ['field_test' => 'value:"Body", summary:"Summary", format:"basic_html"'],
      [
        'field_test' => [[
          'value' => 'Body',
          'summary' => 'Summary',
          'format' => 'basic_html',
        ],
        ],
      ],
    ];
    // Row 14: compound named, single (address).
    yield '14. compound named, single (address)' => [
      ['field_test' => 'country:"BE", locality:"Brussel", postal_code:"1000"'],
      [
        'field_test' => [[
          'country' => 'BE',
          'locality' => 'Brussel',
          'postal_code' => '1000',
        ],
        ],
      ],
    ];
    // Row 15: compound named, single (daterange).
    yield '15. compound named, single (daterange)' => [
      ['field_test' => 'value:"2026-01-01", end_value:"2026-12-31"'],
      [
        'field_test' => [[
          'value' => '2026-01-01',
          'end_value' => '2026-12-31',
        ],
        ],
      ],
    ];
    // Row 16: compound named, single (file/image).
    yield '16. compound named, single (file/image)' => [
      ['field_test' => 'target_id:"foo.jpg", alt:"A", title:"B"'],
      [
        'field_test' => [[
          'target_id' => 'foo.jpg',
          'alt' => 'A',
          'title' => 'B',
        ],
        ],
      ],
    ];
    // Row 17: compound named, value contains ",".
    yield '17. compound named, value contains ","' => [
      ['field_test' => 'country:"BE", locality:"Brussel, X", postal_code:"1000"'],
      [
        'field_test' => [[
          'country' => 'BE',
          'locality' => 'Brussel, X',
          'postal_code' => '1000',
        ],
        ],
      ],
    ];
    // Row 18: compound named, value contains ":".
    yield '18. compound named, value contains ":"' => [
      ['field_test' => 'street:"Main: 1", country:"BE"'],
      [
        'field_test' => [[
          'street' => 'Main: 1',
          'country' => 'BE',
        ],
        ],
      ],
    ];
    // Row 19: compound named, value contains ";".
    yield '19. compound named, value contains ";"' => [
      ['field_test' => 'street:"A;B", country:"BE"'],
      [
        'field_test' => [[
          'street' => 'A;B',
          'country' => 'BE',
        ],
        ],
      ],
    ];
    // Row 20: compound named, value contains escaped ".
    yield '20. compound named, value contains escaped "' => [
      ['field_test' => 'note:"He said \\"hi\\""'],
      ['field_test' => [['note' => 'He said "hi"']]],
    ];
    // Row 21: compound named, multi (address).
    yield '21. compound named, multi (address)' => [
      ['field_test' => 'country:"BE", locality:"Brussel"; country:"FR", locality:"Paris"'],
      [
        'field_test' => [
        ['country' => 'BE', 'locality' => 'Brussel'],
        ['country' => 'FR', 'locality' => 'Paris'],
        ],
      ],
    ];
    // Row 22: compound named, multi (link, positional gone).
    yield '22. compound named, multi (link)' => [
      ['field_test' => 'title:"Link 1", uri:"http://a"; title:"Link 2", uri:"http://b"'],
      [
        'field_test' => [
        ['title' => 'Link 1', 'uri' => 'http://a'],
        ['title' => 'Link 2', 'uri' => 'http://b'],
        ],
      ],
    ];
    // Row 26: external multicolumn header, single.
    yield '26. external multicolumn header, single' => [
      [
        'field_test:col_a' => 'value_a',
        ':col_b' => 'value_b',
      ],
      [
        'field_test' => [
          0 => ['col_a' => 'value_a', 'col_b' => 'value_b'],
        ],
      ],
    ];
    // Row 26: external multicolumn header, multi.
    yield '26. external multicolumn header, multi-value' => [
      [
        'field_test:col_a' => 'A1, A2',
        ':col_b' => 'B1, B2',
      ],
      [
        'field_test' => [
          0 => ['col_a' => 'A1', 'col_b' => 'B1'],
          1 => ['col_a' => 'A2', 'col_b' => 'B2'],
        ],
      ],
    ];
    // Row 27: whitespace tolerance (compact form).
    yield '27. whitespace tolerance (compact)' => [
      ['field_test' => 'name:"Alice",age:"42"'],
      ['field_test' => [['name' => 'Alice', 'age' => '42']]],
    ];
    // Row 27: whitespace tolerance (spaced form).
    yield '27. whitespace tolerance (spaced)' => [
      ['field_test' => 'name : "Alice" , age : "42"'],
      ['field_test' => [['name' => 'Alice', 'age' => '42']]],
    ];

    // Below: rows beyond the validity table - blank handling, base/ignored
    // field semantics, and parse-error coverage.
    yield 'blank value unsets field' => [
      ['field_test' => ''],
      [],
    ];
    yield 'base field passthrough' => [
      ['title' => 'Some title'],
      ['title' => 'Some title'],
      [],
      ['title'],
    ];
    yield 'mixed configurable and base fields' => [
      ['title' => 'Foo', 'field_test' => 'bar'],
      ['title' => 'Foo', 'field_test' => ['bar']],
      ['field_test'],
      ['title'],
    ];
    yield 'ignored property passes validation' => [
      ['role' => 'administrator', 'field_test' => 'A'],
      ['role' => 'administrator', 'field_test' => ['A']],
      ['field_test'],
      [],
      NULL,
      NULL,
      NULL,
      ['role'],
    ];
    yield 'compound value with escape sequences' => [
      ['field_test' => 'note:"line1\nline2\ttab\\\\back"'],
      ['field_test' => [['note' => "line1\nline2\ttab\\back"]]],
    ];
    yield 'compound whitespace inside quoted string preserved' => [
      ['field_test' => 'note:"  spaced  value  "'],
      ['field_test' => [['note' => '  spaced  value  ']]],
    ];
    yield 'error: orphan column continuation' => [
      [':orphan' => 'value'],
      [],
      NULL,
      NULL,
      \RuntimeException::class,
      NULL,
      'Field name missing for :orphan',
    ];
    yield 'error: unknown field' => [
      ['field_unknown' => 'value'],
      [],
      [],
      [],
      \RuntimeException::class,
      NULL,
      'Field "field_unknown" does not exist on entity type "node".',
    ];
    yield 'error: unclosed quote' => [
      ['field_test' => 'name:"Alice'],
      [],
      NULL,
      NULL,
      ParseException::class,
      'unclosed_quote',
    ];
    yield 'error: unknown escape sequence' => [
      ['field_test' => 'name:"Alice\\xfoo"'],
      [],
      NULL,
      NULL,
      ParseException::class,
      'unknown_escape',
    ];
    yield 'error: bare value in compound column' => [
      ['field_test' => 'name:"OK", broken:bare'],
      [],
      NULL,
      NULL,
      ParseException::class,
      'unquoted_compound_value',
    ];
    yield 'error: empty record' => [
      ['field_test' => 'a:"x";; b:"y"'],
      [],
      NULL,
      NULL,
      ParseException::class,
      'empty_record',
    ];
    yield 'error: empty column' => [
      ['field_test' => 'a:"x",, b:"y"'],
      [],
      NULL,
      NULL,
      ParseException::class,
      'empty_column',
    ];
    yield 'error: unclosed token' => [
      ['field_test' => 'value:[relative:-1 week'],
      [],
      NULL,
      NULL,
      ParseException::class,
      'unclosed_token',
    ];
    yield 'error: unquoted semicolon in scalar' => [
      ['field_test' => 'a;b'],
      [],
      NULL,
      NULL,
      ParseException::class,
      'unquoted_semicolon',
    ];
  }

}
