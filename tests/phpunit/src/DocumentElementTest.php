<?php

declare(strict_types=1);

namespace Drupal\DrupalExtension\Tests;

use Behat\Mink\Driver\BrowserKitDriver;
use Behat\Mink\Driver\DriverInterface;
use Behat\Mink\Element\ElementFinder;
use Behat\Mink\Selector\SelectorsHandler;
use Behat\Mink\Session;
use Drupal\DrupalExtension\Element\DocumentElement;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Tests the DocumentElement class.
 */
#[CoversClass(DocumentElement::class)]
class DocumentElementTest extends TestCase {

  /**
   * Tests getText() with BrowserKitDriver strips head and settings JSON.
   */
  #[DataProvider('dataProviderGetTextBrowserKit')]
  public function testGetTextBrowserKit(string $html, string $expected): void {
    $driver = $this->createMock(BrowserKitDriver::class);
    $driver->method('getContent')->willReturn($html);
    $driver->method('find')->willReturn([]);

    $element = $this->createDocumentElement($driver);
    $this->assertSame($expected, $element->getText());
  }

  /**
   * Provides data for testGetTextBrowserKit().
   */
  public static function dataProviderGetTextBrowserKit(): \Iterator {
    yield 'strips head content' => [
      '<html><head><title>Page Title</title><script>var x = 1;</script></head><body><p>Hello world</p></body></html>',
      'Hello world',
    ];

    yield 'strips drupal settings json' => [
      '<html><body><p>Content</p><script type="application/json" data-drupal-selector="drupal-settings-json">{"key":"value"}</script></body></html>',
      'Content',
    ];

    yield 'strips both head and settings json' => [
      '<html><head><title>Title</title></head><body><p>Body text</p><script type="application/json" data-drupal-selector="drupal-settings-json">{"foo":"bar"}</script></body></html>',
      'Body text',
    ];

    yield 'decodes html entities' => [
      '<html><body><p>Tom &amp; Jerry</p></body></html>',
      'Tom & Jerry',
    ];

    yield 'normalises whitespace' => [
      "<html><body><p>Line one</p>\n\n<p>Line two</p></body></html>",
      'Line one Line two',
    ];

    yield 'empty body' => [
      '<html><head><title>Title</title></head><body></body></html>',
      '',
    ];

    yield 'inline script content remains as text' => [
      '<html><body><p>Before</p><script>alert("hi")</script><p>After</p></body></html>',
      'Beforealert("hi")After',
    ];
  }

  /**
   * Tests getText() with non-BrowserKit driver delegates to parent.
   */
  public function testGetTextNonBrowserKit(): void {
    $driver = $this->createMock(DriverInterface::class);
    $driver->method('getText')->with('//html')->willReturn('Driver text');

    $element = $this->createDocumentElement($driver);
    $this->assertSame('Driver text', $element->getText());
  }

  /**
   * Tests getXpath() returns the expected XPath.
   */
  public function testGetXpath(): void {
    $driver = $this->createMock(DriverInterface::class);
    $element = $this->createDocumentElement($driver);
    $this->assertSame('//html', $element->getXpath());
  }

  /**
   * Tests getContent() returns trimmed driver content.
   */
  public function testGetContent(): void {
    $driver = $this->createMock(DriverInterface::class);
    $driver->method('getContent')->willReturn('  <html><body>Content</body></html>  ');

    $element = $this->createDocumentElement($driver);
    $this->assertSame('<html><body>Content</body></html>', $element->getContent());
  }

  /**
   * Creates a DocumentElement with a mocked session.
   */
  private function createDocumentElement(DriverInterface $driver): DocumentElement {
    $session = $this->createMock(Session::class);
    $session->method('getDriver')->willReturn($driver);
    $session->method('getElementFinder')->willReturn(new ElementFinder($driver, new SelectorsHandler()));
    return new DocumentElement($session);
  }

}
