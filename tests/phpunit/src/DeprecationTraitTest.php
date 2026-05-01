<?php

declare(strict_types=1);

namespace Drupal\DrupalExtension\Tests;

use Drupal\DrupalExtension\DeprecationInterface;
use Drupal\DrupalExtension\DeprecationSuppression;
use Drupal\DrupalExtension\DeprecationTrait;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Tests the runtime layer's suppression decision.
 *
 * 'fwrite(STDERR, ...)' bypasses PHP output buffering, so this suite verifies
 * the decision via 'isDeprecationSuppressed()' rather than the side effect.
 * 'DeprecationSuppressionTest' covers the resolution helper directly.
 */
#[CoversClass(DeprecationTrait::class)]
class DeprecationTraitTest extends TestCase {

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
   * Tests the suppression decision under different config + env combinations.
   *
   * @param array<string, mixed> $parameters
   *   Parameters to seed via 'ParametersTrait::setParameters()'.
   * @param string|null $env_value
   *   Env var value to set for the test, or NULL to leave unset.
   * @param bool $expected
   *   Expected return value of 'isDeprecationSuppressed()'.
   */
  #[DataProvider('dataProviderIsDeprecationSuppressed')]
  public function testIsDeprecationSuppressed(array $parameters, ?string $env_value, bool $expected): void {
    if ($env_value !== NULL) {
      putenv(DeprecationSuppression::ENV_VAR . '=' . $env_value);
    }

    $consumer = new TestableDeprecationConsumer();
    $consumer->setParameters($parameters);

    $this->assertSame($expected, $consumer->callIsDeprecationSuppressed());
  }

  /**
   * Provides data for testIsDeprecationSuppressed().
   */
  public static function dataProviderIsDeprecationSuppressed(): \Iterator {
    yield 'no parameters, no env, defaults to off' => [[], NULL, FALSE];
    yield 'config FALSE, no env, off' => [['suppress_deprecations' => FALSE], NULL, FALSE];
    yield 'config TRUE, no env, on' => [['suppress_deprecations' => TRUE], NULL, TRUE];
    yield 'config TRUE, env "0", forced off' => [['suppress_deprecations' => TRUE], '0', FALSE];
    yield 'config FALSE, env "1", forced on' => [['suppress_deprecations' => FALSE], '1', TRUE];
    yield 'no config, env "yes", on' => [[], 'yes', TRUE];
    yield 'no config, env "off", off' => [[], 'off', FALSE];
    yield 'non-bool parameter ignored' => [['suppress_deprecations' => 'yes'], NULL, FALSE];
  }

}

/**
 * Test consumer that exposes the trait's decision method.
 */
// phpcs:ignore Drupal.Classes.ClassFileName.NoMatch
class TestableDeprecationConsumer implements DeprecationInterface {

  use DeprecationTrait;

  /**
   * Public bridge to the protected suppression check.
   */
  public function callIsDeprecationSuppressed(): bool {
    return $this->isDeprecationSuppressed();
  }

}
