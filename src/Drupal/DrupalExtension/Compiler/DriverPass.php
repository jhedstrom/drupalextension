<?php

declare(strict_types=1);

namespace Drupal\DrupalExtension\Compiler;

use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;

/**
 * Drupal\DrupalExtension container compilation pass.
 */
class DriverPass implements CompilerPassInterface {

  /**
   * Register Drupal drivers.
   */
  public function process(ContainerBuilder $container): void {
    if (!$container->hasDefinition('drupal.drupal')) {
      return;
    }

    $drupal_definition = $container->getDefinition('drupal.drupal');

    foreach ($container->findTaggedServiceIds('drupal.driver') as $id => $attributes) {
      foreach ($attributes as $attribute) {
        if (isset($attribute['alias']) && $name = $attribute['alias']) {
          $drupal_definition->addMethodCall('registerDriver', [$name, new Reference($id)]);
        }
      }

      // The DrupalDriver takes a single Core via setCore(). Resolve the
      // first service tagged 'drupal.core' and inject it.
      if ('drupal.driver.drupal' !== $id) {
        continue;
      }

      $core_ids = array_keys($container->findTaggedServiceIds('drupal.core'));

      if ($core_ids === []) {
        continue;
      }

      $container->getDefinition($id)->addMethodCall('setCore', [new Reference($core_ids[0])]);
    }

    $drupal_definition->addMethodCall('setDefaultDriverName', [$container->getParameter('drupal.drupal.default_driver')]);
  }

}
