<?php

declare(strict_types=1);

namespace Drupal\DrupalExtension\Tests;

use Behat\Mink\Session;
use Drupal\DrupalExtension\Context\Traits\AjaxTrait;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Tests the verbose timeout-message formatter on AjaxTrait.
 *
 * The instance methods of the trait drive a live Mink session, but the
 * 'formatTimeoutMessage()' helper is a pure function and is the bit users
 * read when an AJAX wait fails. It is exercised here through a test consumer
 * so that the message format is locked down without needing a real browser.
 */
#[CoversClass(AjaxTrait::class)]
class AjaxTraitTest extends TestCase {

  /**
   * Asserts the rendered exception message for a given input shape.
   *
   * @param int $timeout
   *   The configured AJAX timeout in seconds.
   * @param array<string, mixed>|null $diagnostics
   *   The diagnostic payload, or NULL when the browser probe was skipped.
   * @param array<string, string>|null $event_data
   *   The Behat scope payload, or NULL when called outside a hook.
   * @param string $expected
   *   The expected verbatim message.
   */
  #[DataProvider('dataProviderFormatTimeoutMessage')]
  public function testFormatTimeoutMessage(int $timeout, ?array $diagnostics, ?array $event_data, string $expected): void {
    $actual = TestableAjaxConsumer::callFormatTimeoutMessage($timeout, $diagnostics, $event_data);
    $this->assertSame($expected, $actual);
  }

  /**
   * Provides data for testFormatTimeoutMessage().
   *
   * @return \Iterator<string, array{int, array<string, mixed>|null, array<string, string>|null, string}>
   *   Test cases keyed by description.
   */
  public static function dataProviderFormatTimeoutMessage(): \Iterator {
    yield 'timeout only, no diagnostics, no event' => [
      5,
      NULL,
      NULL,
      'Unable to complete AJAX request after 5 second(s).',
    ];

    yield 'timeout plus event only - simulates pre-#479 behaviour' => [
      10,
      NULL,
      [
        'hook' => 'step.before',
        'feature' => 'My Feature',
        'step' => 'I click the button',
        'suite' => 'default',
      ],
      "Unable to complete AJAX request after 10 second(s).\n\nHook: step.before\nFeature: My Feature\nStep: I click the button\nSuite: default",
    ];

    yield 'timeout plus full diagnostics with one Drupal AJAX instance' => [
      5,
      [
        'url' => 'http://example.com/node/add',
        'jquery_active' => 2,
        'jquery_animated' => 0,
        'drupal_ajax_instances' => [
          [
            'index' => 0,
            'selector' => '#edit-submit',
            'event' => 'mousedown',
            'url' => '/system/ajax',
          ],
        ],
      ],
      NULL,
      "Unable to complete AJAX request after 5 second(s).\n\nURL: http://example.com/node/add\njQuery active requests: 2\njQuery animated elements: 0\nDrupal AJAX instances active: 1\n  - selector: '#edit-submit', event: 'mousedown', url: '/system/ajax', index: '0'",
    ];

    yield 'diagnostics plus event combined into one message' => [
      5,
      [
        'url' => 'http://example.com/node/add',
        'jquery_active' => 1,
        'jquery_animated' => 0,
        'drupal_ajax_instances' => [],
      ],
      [
        'hook' => 'step.after',
        'feature' => 'Conference',
        'step' => 'I press "Save"',
        'suite' => 'default',
      ],
      "Unable to complete AJAX request after 5 second(s).\n\nURL: http://example.com/node/add\njQuery active requests: 1\njQuery animated elements: 0\nDrupal AJAX instances active: 0\n\nHook: step.after\nFeature: Conference\nStep: I press \"Save\"\nSuite: default",
    ];

    yield 'jquery_active 0 renders as 0, not unavailable' => [
      5,
      [
        'url' => 'http://example.com/',
        'jquery_active' => 0,
        'jquery_animated' => 0,
        'drupal_ajax_instances' => [],
      ],
      NULL,
      "Unable to complete AJAX request after 5 second(s).\n\nURL: http://example.com/\njQuery active requests: 0\njQuery animated elements: 0\nDrupal AJAX instances active: 0",
    ];

    yield 'jQuery undefined renders the scalars as unavailable' => [
      5,
      [
        'url' => 'http://example.com/',
        'jquery_active' => NULL,
        'jquery_animated' => NULL,
        'drupal_ajax_instances' => [],
      ],
      NULL,
      "Unable to complete AJAX request after 5 second(s).\n\nURL: http://example.com/\njQuery active requests: unavailable\njQuery animated elements: unavailable\nDrupal AJAX instances active: 0",
    ];

    yield 'multiple Drupal AJAX instances each get their own bullet' => [
      5,
      [
        'url' => 'http://example.com/',
        'jquery_active' => 0,
        'jquery_animated' => 0,
        'drupal_ajax_instances' => [
          ['index' => 0, 'selector' => '#a', 'event' => 'click', 'url' => '/a'],
          ['index' => 1, 'selector' => '#b', 'event' => 'change', 'url' => '/b'],
        ],
      ],
      NULL,
      "Unable to complete AJAX request after 5 second(s).\n\nURL: http://example.com/\njQuery active requests: 0\njQuery animated elements: 0\nDrupal AJAX instances active: 2\n  - selector: '#a', event: 'click', url: '/a', index: '0'\n  - selector: '#b', event: 'change', url: '/b', index: '1'",
    ];

    yield 'instance with no recognised fields falls back to no details' => [
      5,
      [
        'url' => 'http://example.com/',
        'jquery_active' => 0,
        'jquery_animated' => 0,
        'drupal_ajax_instances' => [
          ['something_else' => 'ignored'],
        ],
      ],
      NULL,
      "Unable to complete AJAX request after 5 second(s).\n\nURL: http://example.com/\njQuery active requests: 0\njQuery animated elements: 0\nDrupal AJAX instances active: 1\n  - (no details)",
    ];

    yield 'malformed instance entry is skipped without crashing' => [
      5,
      [
        'url' => 'http://example.com/',
        'jquery_active' => 0,
        'jquery_animated' => 0,
        'drupal_ajax_instances' => [
          'not-an-array',
          ['selector' => '#valid'],
        ],
      ],
      NULL,
      "Unable to complete AJAX request after 5 second(s).\n\nURL: http://example.com/\njQuery active requests: 0\njQuery animated elements: 0\nDrupal AJAX instances active: 2\n  - selector: '#valid'",
    ];

    yield 'missing url key omits the URL line entirely' => [
      5,
      [
        'jquery_active' => 0,
        'jquery_animated' => 0,
        'drupal_ajax_instances' => [],
      ],
      NULL,
      "Unable to complete AJAX request after 5 second(s).\n\njQuery active requests: 0\njQuery animated elements: 0\nDrupal AJAX instances active: 0",
    ];

    yield 'non-string url is skipped, scalars still rendered' => [
      5,
      [
        'url' => ['not', 'a', 'string'],
        'jquery_active' => 0,
        'jquery_animated' => 0,
        'drupal_ajax_instances' => [],
      ],
      NULL,
      "Unable to complete AJAX request after 5 second(s).\n\njQuery active requests: 0\njQuery animated elements: 0\nDrupal AJAX instances active: 0",
    ];

    yield 'non-array drupal_ajax_instances is treated as empty' => [
      5,
      [
        'url' => 'http://example.com/',
        'jquery_active' => 0,
        'jquery_animated' => 0,
        'drupal_ajax_instances' => 'not-an-array',
      ],
      NULL,
      "Unable to complete AJAX request after 5 second(s).\n\nURL: http://example.com/\njQuery active requests: 0\njQuery animated elements: 0\nDrupal AJAX instances active: 0",
    ];
  }

}

/**
 * Test consumer that exposes the trait's protected static formatter.
 *
 * Mirrors the 'TestableDeprecationConsumer' pattern from
 * 'DeprecationTraitTest' so the public API of the trait is not changed.
 * The 'getSession()' stub keeps PHPStan satisfied - the consumer class
 * is only ever used to call the static formatter, never the instance
 * methods that need a real Mink session.
 */
// phpcs:ignore Drupal.Classes.ClassFileName.NoMatch
class TestableAjaxConsumer {

  use AjaxTrait;

  /**
   * Public bridge to the protected static formatter.
   *
   * @param int $timeout
   *   The configured AJAX timeout in seconds.
   * @param array<string, mixed>|null $diagnostics
   *   The diagnostic payload, or NULL when the browser probe was skipped.
   * @param array<string, string>|null $event_data
   *   The Behat scope payload, or NULL when called outside a hook.
   */
  public static function callFormatTimeoutMessage(int $timeout, ?array $diagnostics, ?array $event_data): string {
    return self::formatTimeoutMessage($timeout, $diagnostics, $event_data);
  }

  /**
   * Stub keeping PHPStan happy - the formatter never calls this.
   */
  protected function getSession(): Session {
    throw new \LogicException('TestableAjaxConsumer::getSession() should not be called - this fixture is for static formatter tests only.');
  }

}
