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
 * Tests that 'regions' configuration flows through to container parameters.
 */
#[CoversMethod(DrupalExtension::class, 'configure')]
#[CoversMethod(DrupalExtension::class, 'loadParameters')]
class DrupalExtensionRegionsTest extends TestCase {

  /**
   * Tests that the configured region map reaches the container parameters.
   *
   * @param array<string, mixed> $config
   *   The DrupalExtension configuration (raw, before schema normalisation).
   * @param array<string, string> $expected
   *   The region map expected on the 'drupal.regions' container parameter.
   */
  #[DataProvider('dataProviderRegions')]
  public function testRegions(array $config, array $expected): void {
    $extension = new TestableDrupalExtension();

    $builder = new ArrayNodeDefinition('drupal');
    $extension->configure($builder);
    $tree = $builder->getNode(TRUE);
    $processed = $tree->finalize($tree->normalize($config));

    $container = new ContainerBuilder();
    $extension->callLoadParameters($container, $processed);

    $this->assertSame($expected, $container->getParameter('drupal.regions'));

    // The same map is surfaced through 'drupal.parameters', so contexts using
    // ParametersTrait resolve the value the 'region' Mink selector uses.
    $parameters = $container->getParameter('drupal.parameters');
    $this->assertIsArray($parameters);
    $this->assertSame($expected, $parameters['regions']);
  }

  /**
   * Provides data for testRegions().
   */
  public static function dataProviderRegions(): \Iterator {
    yield 'configured map is exposed' => [
      ['regions' => ['Header' => '#header', 'Content' => '#main']],
      ['Header' => '#header', 'Content' => '#main'],
    ];

    yield 'no regions yields an empty map' => [
      [],
      [],
    ];
  }

}

/**
 * Test-only subclass that exposes the protected 'loadParameters' method.
 */
// phpcs:ignore Drupal.Classes.ClassFileName.NoMatch
class TestableDrupalExtension extends DrupalExtension {

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
