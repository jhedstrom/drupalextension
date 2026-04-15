<?php

declare(strict_types=1);

namespace Drupal\DrupalExtension\Tests;

use Behat\Gherkin\Node\TableNode;
use PHPUnit\Framework\MockObject\MockObject;
use Drupal\Driver\DriverInterface;
use Drupal\DrupalDriverManagerInterface;
use Drupal\DrupalExtension\Context\ConfigContext;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Tests the ConfigContext class.
 */
#[CoversClass(ConfigContext::class)]
class ConfigContextTest extends TestCase {

  /**
   * The context under test.
   */
  protected ConfigContext $context;

  /**
   * The mocked driver.
   */
  protected MockObject $driver;

  /**
   * Sets up test fixtures.
   */
  protected function setUp(): void {
    $this->context = new ConfigContext();
    $this->driver = $this->createMock(DriverInterface::class);
    $drupal = $this->createMock(DrupalDriverManagerInterface::class);
    $drupal->method('getDriver')->willReturn($this->driver);
    $this->context->setDrupal($drupal);
  }

  /**
   * Tests that setBasicConfig stores backup values correctly.
   */
  #[DataProvider('dataProviderSetBasicConfigBackup')]
  public function testSetBasicConfigBackup(array $operations, array $expected_backup): void {
    $getReturns = array_column($operations, 'original');
    $this->driver->method('configGet')->willReturnOnConsecutiveCalls(...$getReturns);
    $this->driver->method('configSet');

    foreach ($operations as $operation) {
      $this->context->setBasicConfig($operation['name'], $operation['key'], $operation['new_value']);
    }

    $config = new \ReflectionProperty(ConfigContext::class, 'config');
    $stored = $config->getValue($this->context);

    foreach ($expected_backup as $name => $keys) {
      foreach ($keys as $key => $value) {
        $this->assertSame($value, $stored[$name][$key]);
      }
    }
  }

  /**
   * Provides data for testSetBasicConfigBackup().
   */
  public static function dataProviderSetBasicConfigBackup(): \Iterator {
    yield 'single key backup' => [
          [['name' => 'system.site', 'key' => 'name', 'original' => 'Original', 'new_value' => 'New']],
          ['system.site' => ['name' => 'Original']],
    ];
    yield 'does not overwrite backup on second set' => [
          [
              ['name' => 'system.site', 'key' => 'name', 'original' => 'Original', 'new_value' => 'First'],
              ['name' => 'system.site', 'key' => 'name', 'original' => 'First', 'new_value' => 'Second'],
          ],
          ['system.site' => ['name' => 'Original']],
    ];
    yield 'tracks multiple keys in same config' => [
          [
              ['name' => 'system.site', 'key' => 'name', 'original' => 'Original Name', 'new_value' => 'New Name'],
              ['name' => 'system.site', 'key' => 'slogan', 'original' => 'Original Slogan', 'new_value' => 'New Slogan'],
          ],
          ['system.site' => ['name' => 'Original Name', 'slogan' => 'Original Slogan']],
    ];
    yield 'tracks multiple config objects' => [
          [
              ['name' => 'system.site', 'key' => 'name', 'original' => 'Site', 'new_value' => 'New Site'],
              ['name' => 'system.performance', 'key' => 'cache', 'original' => '1', 'new_value' => '0'],
          ],
          ['system.site' => ['name' => 'Site'], 'system.performance' => ['cache' => '1']],
    ];
  }

  /**
   * Tests that cleanConfig restores all original values.
   */
  public function testCleanConfigRestoresAllValues(): void {
    $this->driver->method('configGet')->willReturn('Original');

    $setArgs = [];
    $this->driver->method('configSet')
      ->willReturnCallback(function (string $name, string $key, mixed $value) use (&$setArgs): void {
                $setArgs[] = [$name, $key, $value];
      });

    $this->context->setBasicConfig('system.site', 'name', 'New Name');
    $this->context->cleanConfig();

    $restoreCall = end($setArgs);
    $this->assertSame(['system.site', 'name', 'Original'], $restoreCall);

    $config = new \ReflectionProperty(ConfigContext::class, 'config');
    $this->assertSame([], $config->getValue($this->context));
  }

  /**
   * Tests that setBasicConfig delegates to setConfig.
   */
  public function testSetBasicConfigDelegatesToSetConfig(): void {
    $this->driver->method('configGet')->willReturn('old');
    $this->driver->expects($this->once())
      ->method('configSet')
      ->with('system.site', 'name', 'New');

    $this->context->setBasicConfig('system.site', 'name', 'New');
  }

  /**
   * Tests that setBasicConfig coerces scalar string values to native types.
   */
  #[DataProvider('dataProviderSetBasicConfigCoercion')]
  public function testSetBasicConfigCoercion(string $input, mixed $expected): void {
    $this->driver->method('configGet')->willReturn('original');

    $actual = NULL;
    $this->driver->method('configSet')
      ->willReturnCallback(function (string $name, string $key, mixed $value) use (&$actual): void {
        $actual = $value;
      });

    $this->context->setBasicConfig('test.config', 'key', $input);
    $this->assertSame($expected, $actual);
  }

  /**
   * Provides data for testSetBasicConfigCoercion().
   */
  public static function dataProviderSetBasicConfigCoercion(): \Iterator {
    yield 'boolean true' => ['true', TRUE];
    yield 'boolean false' => ['false', FALSE];
    yield 'null' => ['null', NULL];
    yield 'integer' => ['50', 50];
    yield 'negative integer' => ['-1', -1];
    yield 'float' => ['3.14', 3.14];
    yield 'string stays string' => ['My Site', 'My Site'];
    yield 'string TRUE not coerced' => ['TRUE', 'TRUE'];
    yield 'string False not coerced' => ['False', 'False'];
    yield 'string Null not coerced' => ['Null', 'Null'];
    yield 'empty string stays string' => ['', ''];
    yield 'path stays string' => ['/node', '/node'];
  }

  /**
   * Tests that setComplexConfig coerces table values to native types.
   */
  public function testSetComplexConfigCoercion(): void {
    $this->driver->method('configGet')->willReturn([]);

    $actual = NULL;
    $this->driver->method('configSet')
      ->willReturnCallback(function (string $name, string $key, mixed $value) use (&$actual): void {
        $actual = $value;
      });

    $table = new TableNode([
      ['key', 'value'],
      ['preprocess', 'true'],
      ['gzip', 'false'],
      ['max_age', '300'],
    ]);

    $this->context->setComplexConfig('system.performance', 'css', $table);

    $this->assertSame(['preprocess' => TRUE, 'gzip' => FALSE, 'max_age' => 300], $actual);
  }

  /**
   * Tests that setComplexConfig decodes JSON array/object values in table rows.
   */
  public function testSetComplexConfigJsonDecode(): void {
    $this->driver->method('configGet')->willReturn([]);

    $actual = NULL;
    $this->driver->method('configSet')
      ->willReturnCallback(function (string $name, string $key, mixed $value) use (&$actual): void {
        $actual = $value;
      });

    $table = new TableNode([
      ['key', 'value'],
      ['nested', '{"foo": "bar", "baz": 1}'],
      ['list', '[1, 2, 3]'],
      ['plain', '/node'],
    ]);

    $this->context->setComplexConfig('some.config', 'settings', $table);

    $this->assertSame([
      'nested' => ['foo' => 'bar', 'baz' => 1],
      'list' => [1, 2, 3],
      'plain' => '/node',
    ], $actual);
  }

}
