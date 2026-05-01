<?php

declare(strict_types=1);

namespace Drupal\DrupalExtension\Tests;

use Drupal\DrupalExtension\ServiceContainer\DrupalExtension;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Tests the regions/region_map merge logic in 'loadParameters()'.
 */
#[CoversMethod(DrupalExtension::class, 'configure')]
#[CoversMethod(DrupalExtension::class, 'loadParameters')]
class DrupalExtensionRegionsTest extends TestCase {

  /**
   * Tests merge behaviour and deprecation emission across config shapes.
   *
   * @param array<string, mixed> $config
   *   The DrupalExtension configuration (raw, before schema normalisation).
   * @param array<string, string> $expected
   *   The expected merged region map.
   * @param bool $expectsDeprecation
   *   Whether the call should emit the region_map deprecation notice.
   */
  #[DataProvider('dataProviderRegionsMergeAndDeprecation')]
  public function testRegionsMergeAndDeprecation(array $config, array $expected, bool $expectsDeprecation): void {
    $extension = new TestableDrupalExtension();

    $builder = new ArrayNodeDefinition('drupal');
    $extension->configure($builder);
    $tree = $builder->getNode(TRUE);
    $processed = $tree->finalize($tree->normalize($config));

    $container = new ContainerBuilder();
    $extension->callLoadParameters($container, $processed);

    $this->assertSame($expected, $container->getParameter('drupal.regions'));

    if ($expectsDeprecation) {
      $this->assertCount(1, $extension->capturedDeprecations);
      $this->assertStringContainsString('region_map', $extension->capturedDeprecations[0]);
      $this->assertStringContainsString('deprecated in drupal-extension:6.0.0', $extension->capturedDeprecations[0]);
    }
    else {
      $this->assertSame([], $extension->capturedDeprecations);
    }

    // The merged map is surfaced through 'drupal.parameters' too, so
    // contexts using ParametersTrait see the same value as RegionSelector.
    $parameters = $container->getParameter('drupal.parameters');
    $this->assertIsArray($parameters);
    $this->assertSame($expected, $parameters['regions']);
    $this->assertArrayNotHasKey('region_map', $parameters);
  }

  /**
   * Provides data for testRegionsMergeAndDeprecation().
   */
  public static function dataProviderRegionsMergeAndDeprecation(): \Iterator {
    yield 'regions only, no deprecation' => [
      ['regions' => ['Header' => '#header', 'Content' => '#main']],
      ['Header' => '#header', 'Content' => '#main'],
      FALSE,
    ];

    yield 'region_map only emits deprecation' => [
      ['region_map' => ['Header' => '#header']],
      ['Header' => '#header'],
      TRUE,
    ];

    yield 'regions wins on key collision and merges with legacy' => [
      [
        'regions' => ['Header' => '#new-header'],
        'region_map' => ['Header' => '#old-header', 'Footer' => '#footer'],
      ],
      ['Header' => '#new-header', 'Footer' => '#footer'],
      TRUE,
    ];

    yield 'neither set yields empty map and no deprecation' => [
      [],
      [],
      FALSE,
    ];
  }

}

/**
 * Test-only subclass that captures deprecations and exposes 'loadParameters'.
 */
// phpcs:ignore Drupal.Classes.ClassFileName.NoMatch
class TestableDrupalExtension extends DrupalExtension {

  /**
   * Captured deprecation messages.
   *
   * @var array<int, string>
   */
  public array $capturedDeprecations = [];

  /**
   * {@inheritdoc}
   */
  protected function emitDeprecation(string $message): void {
    $this->capturedDeprecations[] = $message;
  }

  /**
   * Public bridge to the protected loadParameters method.
   *
   * @param \Symfony\Component\DependencyInjection\ContainerBuilder $container
   *   The container builder to populate.
   * @param array<string, mixed> $config
   *   The processed configuration array.
   */
  public function callLoadParameters(ContainerBuilder $container, array $config): void {
    $this->loadParameters($container, $config);
  }

}
