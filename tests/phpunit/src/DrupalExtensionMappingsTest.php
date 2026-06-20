<?php

declare(strict_types=1);

namespace Drupal\DrupalExtension\Tests;

use Drupal\DrupalExtension\ServiceContainer\DrupalExtension;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Tests the grouped-mappings flatten and uniqueness logic.
 */
#[CoversMethod(DrupalExtension::class, 'configure')]
#[CoversMethod(DrupalExtension::class, 'loadParameters')]
#[CoversMethod(DrupalExtension::class, 'flattenMappings')]
class DrupalExtensionMappingsTest extends TestCase {

  /**
   * Tests that grouped mappings flatten to a single key => value map.
   *
   * @param array<string, mixed> $config
   *   The DrupalExtension configuration (raw, before schema normalisation).
   * @param array<string, string> $expected
   *   The expected flattened mapping map.
   */
  #[DataProvider('dataProviderMappingsFlatten')]
  public function testMappingsFlatten(array $config, array $expected): void {
    $parameters = $this->process($config);
    $this->assertSame($expected, $parameters['mappings']);
  }

  /**
   * Provides data for testMappingsFlatten().
   */
  public static function dataProviderMappingsFlatten(): \Iterator {
    yield 'single group flattens to its entries' => [
      ['mappings' => ['paths' => ['User Registration' => '/user/register', 'User Login' => '/user/login']]],
      ['User Registration' => '/user/register', 'User Login' => '/user/login'],
    ];

    yield 'multiple groups merge into one map' => [
      ['mappings' => ['paths' => ['Home' => '/'], 'text' => ['Greeting' => 'Hello']]],
      ['Home' => '/', 'Greeting' => 'Hello'],
    ];

    yield 'no mappings yields an empty map' => [
      [],
      [],
    ];
  }

  /**
   * Tests that the same key in two groups is rejected as ambiguous.
   */
  public function testDuplicateKeyAcrossGroupsThrows(): void {
    $this->expectException(InvalidConfigurationException::class);
    $this->expectExceptionMessage('Duplicate mapping key "Home" found in groups "paths" and "aliases"');
    $this->process(['mappings' => ['paths' => ['Home' => '/'], 'aliases' => ['Home' => '/front']]]);
  }

  /**
   * Runs config through configure() and loadParameters() and returns params.
   *
   * @param array<string, mixed> $config
   *   The DrupalExtension configuration (raw, before schema normalisation).
   *
   * @return array<string, mixed>
   *   The resulting 'drupal.parameters' container parameter.
   */
  protected function process(array $config): array {
    $extension = new MappingsTestableDrupalExtension();

    $builder = new ArrayNodeDefinition('drupal');
    $extension->configure($builder);
    $tree = $builder->getNode(TRUE);
    $processed = $tree->finalize($tree->normalize($config));

    $container = new ContainerBuilder();
    $extension->callLoadParameters($container, $processed);

    $parameters = $container->getParameter('drupal.parameters');
    $this->assertIsArray($parameters);

    return $parameters;
  }

}

/**
 * Test-only subclass that exposes the protected 'loadParameters' method.
 */
// phpcs:ignore Drupal.Classes.ClassFileName.NoMatch
class MappingsTestableDrupalExtension extends DrupalExtension {

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
