<?php

declare(strict_types=1);

namespace Drupal\DrupalExtension\Tests;

use Drupal\DrupalExtension\DeprecationSuppression;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Tests the deprecation-suppression resolution rules.
 */
#[CoversClass(DeprecationSuppression::class)]
class DeprecationSuppressionTest extends TestCase {

  /**
   * Snapshot of the env var to restore after each test, NULL when unset.
   */
  private ?string $envBackup;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    $existing = getenv(DeprecationSuppression::ENV_VAR);
    $this->envBackup = $existing === FALSE ? NULL : $existing;
    putenv(DeprecationSuppression::ENV_VAR);
  }

  /**
   * {@inheritdoc}
   */
  protected function tearDown(): void {
    if ($this->envBackup === NULL) {
      putenv(DeprecationSuppression::ENV_VAR);
    }
    else {
      putenv(DeprecationSuppression::ENV_VAR . '=' . $this->envBackup);
    }
  }

  /**
   * Tests resolution when the env var is unset (config is the only input).
   */
  #[DataProvider('dataProviderConfigOnly')]
  public function testConfigOnly(?bool $config_value, bool $expected): void {
    $this->assertSame($expected, DeprecationSuppression::shouldSuppress($config_value));
  }

  /**
   * Provides data for testConfigOnly().
   */
  public static function dataProviderConfigOnly(): \Iterator {
    yield 'NULL config defaults to off' => [NULL, FALSE];
    yield 'FALSE config does not suppress' => [FALSE, FALSE];
    yield 'TRUE config suppresses' => [TRUE, TRUE];
  }

  /**
   * Tests env var override across the parseable boolean spellings.
   */
  #[DataProvider('dataProviderEnvOverride')]
  public function testEnvOverride(string $raw, ?bool $config_value, bool $expected): void {
    putenv(DeprecationSuppression::ENV_VAR . '=' . $raw);
    $this->assertSame($expected, DeprecationSuppression::shouldSuppress($config_value));
  }

  /**
   * Provides data for testEnvOverride().
   */
  public static function dataProviderEnvOverride(): \Iterator {
    yield 'env "1" forces suppress (config NULL)' => ['1', NULL, TRUE];
    yield 'env "true" forces suppress (config FALSE)' => ['true', FALSE, TRUE];
    yield 'env "TRUE" is case-insensitive' => ['TRUE', FALSE, TRUE];
    yield 'env "yes" forces suppress' => ['yes', FALSE, TRUE];
    yield 'env "on" forces suppress' => ['on', FALSE, TRUE];
    yield 'env "0" forces show even when config TRUE' => ['0', TRUE, FALSE];
    yield 'env "false" forces show' => ['false', TRUE, FALSE];
    yield 'env "no" forces show' => ['no', TRUE, FALSE];
    yield 'env "off" forces show' => ['off', TRUE, FALSE];
    yield 'env trimmed ("  1  ") forces suppress' => ['  1  ', NULL, TRUE];
  }

  /**
   * Tests that an unparseable env value falls back to the config value.
   */
  #[DataProvider('dataProviderEnvUnparseableFallsBackToConfig')]
  public function testEnvUnparseableFallsBackToConfig(string $raw, ?bool $config_value, bool $expected): void {
    putenv(DeprecationSuppression::ENV_VAR . '=' . $raw);
    $this->assertSame($expected, DeprecationSuppression::shouldSuppress($config_value));
  }

  /**
   * Provides data for testEnvUnparseableFallsBackToConfig().
   */
  public static function dataProviderEnvUnparseableFallsBackToConfig(): \Iterator {
    yield 'garbage env, NULL config' => ['maybe', NULL, FALSE];
    yield 'garbage env, TRUE config' => ['maybe', TRUE, TRUE];
    yield 'garbage env, FALSE config' => ['maybe', FALSE, FALSE];
    yield 'empty env is treated as unset' => ['', TRUE, TRUE];
  }

}
