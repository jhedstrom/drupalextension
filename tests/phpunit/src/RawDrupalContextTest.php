<?php

declare(strict_types=1);

namespace Drupal\DrupalExtension\Tests;

use Drupal\Driver\Core\CoreInterface;
use Drupal\Driver\Core\Field\FieldClassifierInterface;
use Drupal\Driver\DriverInterface;
use Drupal\Driver\DrupalDriver;
use Drupal\Driver\Entity\EntityStub;
use Drupal\DrupalExtension\Manager\DriverManagerInterface;
use Drupal\DrupalExtension\Context\RawDrupalContext;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
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
   * Snapshot of the env var to restore after each test, NULL when unset.
   */
  private ?string $envBackup;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    $existing = getenv('BEHAT_DRUPALEXTENSION_DISABLE_CLEANUP');
    $this->envBackup = $existing === FALSE ? NULL : $existing;
    putenv('BEHAT_DRUPALEXTENSION_DISABLE_CLEANUP');
  }

  /**
   * {@inheritdoc}
   */
  protected function tearDown(): void {
    if ($this->envBackup === NULL) {
      putenv('BEHAT_DRUPALEXTENSION_DISABLE_CLEANUP');
    }
    else {
      putenv('BEHAT_DRUPALEXTENSION_DISABLE_CLEANUP=' . $this->envBackup);
    }
  }

  /**
   * Tests that parseEntityFields throws when the driver is not a DrupalDriver.
   */
  public function testParseEntityFieldsRequiresDrupalDriver(): void {
    $driver = $this->createMock(DriverInterface::class);

    $drupal = $this->createMock(DriverManagerInterface::class);
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
    $context->setParameters(['field_parser' => 'legacy']);

    $stub = new EntityStub('node', NULL, ['field_test' => 'A - B']);
    $context->parseEntityFields($stub);

    $this->assertSame(
      ['field_test' => [['A', 'B']]],
      $stub->getValues(),
    );
  }

  /**
   * Tests the cleanup decision under different env var values.
   *
   * @param string|null $env_value
   *   Env var value to set for the test, or NULL to leave unset.
   * @param bool $expected
   *   Expected return value of 'shouldCleanup()'.
   */
  #[DataProvider('dataProviderShouldCleanup')]
  public function testShouldCleanup(?string $env_value, bool $expected): void {
    if ($env_value !== NULL) {
      putenv('BEHAT_DRUPALEXTENSION_DISABLE_CLEANUP=' . $env_value);
    }

    $context = new TestableRawDrupalContext();

    $this->assertSame($expected, $context->callShouldCleanup());
  }

  /**
   * Provides data for testShouldCleanup().
   */
  public static function dataProviderShouldCleanup(): \Iterator {
    yield 'unset env, defaults to cleanup on' => [NULL, TRUE];
    yield 'empty env, cleanup on' => ['', TRUE];
    yield 'env "1", cleanup off' => ['1', FALSE];
    yield 'env "true", cleanup off' => ['true', FALSE];
    yield 'env "TRUE", case-insensitive, cleanup off' => ['TRUE', FALSE];
    yield 'env "yes", cleanup off' => ['yes', FALSE];
    yield 'env "on", cleanup off' => ['on', FALSE];
    yield 'env " 1 ", whitespace trimmed, cleanup off' => [' 1 ', FALSE];
    yield 'env "0", cleanup on' => ['0', TRUE];
    yield 'env "false", cleanup on' => ['false', TRUE];
    yield 'env "no", cleanup on' => ['no', TRUE];
    yield 'env "off", cleanup on' => ['off', TRUE];
    yield 'env "maybe", unrecognised, cleanup on' => ['maybe', TRUE];
    yield 'env "2", unrecognised numeric, cleanup on' => ['2', TRUE];
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

    $drupal = $this->createMock(DriverManagerInterface::class);
    $drupal->method('getDriver')->willReturn($driver);

    $context = new RawDrupalContext();
    $context->setDrupal($drupal);

    return $context;
  }

}

/**
 * Test consumer that exposes the protected cleanup decision.
 */
// phpcs:ignore Drupal.Classes.ClassFileName.NoMatch
class TestableRawDrupalContext extends RawDrupalContext {

  /**
   * Public bridge to the protected cleanup decision.
   */
  public function callShouldCleanup(): bool {
    return $this->shouldCleanup();
  }

}
