<?php

declare(strict_types=1);

namespace Drupal\DrupalExtension\Tests;

use Drupal\Driver\Core\CoreInterface;
use Drupal\Driver\Core\Field\FieldClassifierInterface;
use Drupal\Driver\DriverInterface;
use Drupal\Driver\DrupalDriver;
use Drupal\Driver\Entity\EntityStub;
use Drupal\DrupalDriverManagerInterface;
use Drupal\DrupalExtension\Context\RawDrupalContext;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * Integration tests for RawDrupalContext.
 *
 * The full parsing matrices for each implementation live in their own
 * tests ('LegacyEntityFieldParserTest' and 'EntityFieldParserTest'). This
 * class tests only the context-level concerns: the driver precondition,
 * the field_parser flag, and that parseEntityFields() delegates to the
 * selected parser.
 */
#[CoversClass(RawDrupalContext::class)]
class RawDrupalContextTest extends TestCase {

  /**
   * Tests that parseEntityFields throws when the driver is not a DrupalDriver.
   */
  public function testParseEntityFieldsRequiresDrupalDriver(): void {
    $driver = $this->createMock(DriverInterface::class);

    $drupal = $this->createMock(DrupalDriverManagerInterface::class);
    $drupal->method('getDriver')->willReturn($driver);

    $context = new RawDrupalContext();
    $context->setDrupal($drupal);

    $stub = new EntityStub('node', NULL, ['field_test' => 'A']);

    $this->expectException(\RuntimeException::class);
    $this->expectExceptionMessage('does not support field inspection');

    $context->parseEntityFields($stub);
  }

  /**
   * Tests that parseEntityFields uses the modern parser by default.
   */
  public function testParseEntityFieldsUsesModernParserByDefault(): void {
    $context = $this->buildContext();

    $stub = new EntityStub('node', NULL, ['field_test' => 'name:"Alice", age:"42"']);
    $context->parseEntityFields($stub);

    $this->assertSame(
      ['field_test' => [['name' => 'Alice', 'age' => '42']]],
      $stub->getValues(),
    );
  }

  /**
   * Tests that parseEntityFields uses the legacy parser when configured.
   */
  public function testParseEntityFieldsUsesLegacyParserWhenConfigured(): void {
    $context = $this->buildContext();
    $context->setDrupalParameters(['field_parser' => 'legacy']);

    $stub = new EntityStub('node', NULL, ['field_test' => 'A - B']);
    $context->parseEntityFields($stub);

    $this->assertSame(
      ['field_test' => [['A', 'B']]],
      $stub->getValues(),
    );
  }

  /**
   * Builds a context wired to an everything-configurable classifier.
   */
  protected function buildContext(): RawDrupalContext {
    $classifier = $this->createMock(FieldClassifierInterface::class);
    $classifier->method('fieldIsConfigurable')->willReturn(TRUE);

    $core = $this->createMock(CoreInterface::class);
    $core->method('getFieldClassifier')->willReturn($classifier);

    $driver = $this->createMock(DrupalDriver::class);
    $driver->method('getCore')->willReturn($core);

    $drupal = $this->createMock(DrupalDriverManagerInterface::class);
    $drupal->method('getDriver')->willReturn($driver);

    $context = new RawDrupalContext();
    $context->setDrupal($drupal);

    return $context;
  }

}
