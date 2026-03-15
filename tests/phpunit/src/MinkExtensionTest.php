<?php

declare(strict_types=1);

namespace Drupal\DrupalExtension\Tests;

use Drupal\MinkExtension\ServiceContainer\MinkExtension;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;

/**
 * Tests the MinkExtension class.
 */
#[CoversClass(MinkExtension::class)]
class MinkExtensionTest extends TestCase {

  /**
   * Minimal config required by parent MinkExtension.
   */
  private const VALID_BASE_CONFIG = [
    'sessions' => ['default' => ['goutte' => []]],
  ];

  /**
   * Tests the AJAX timeout constant value.
   */
  public function testAjaxTimeoutConstant(): void {
    $this->assertSame(5, MinkExtension::AJAX_TIMEOUT);
  }

  /**
   * Tests the configure method with various inputs.
   */
  #[DataProvider('dataProviderConfigure')]
  public function testConfigure(array $input, mixed $expected): void {
    $builder = new ArrayNodeDefinition('test');
    $extension = new MinkExtension();
    $extension->configure($builder);

    $tree = $builder->getNode(TRUE);
    $config = $tree->finalize($tree->normalize(array_merge(self::VALID_BASE_CONFIG, $input)));

    $this->assertSame($expected, $config['ajax_timeout']);
  }

  /**
   * Provides data for testConfigure().
   */
  public static function dataProviderConfigure(): \Iterator {
    yield 'default ajax_timeout' => [[], 5];
    yield 'custom ajax_timeout' => [['ajax_timeout' => 10], 10];
    yield 'zero ajax_timeout' => [['ajax_timeout' => 0], 0];
  }

}
