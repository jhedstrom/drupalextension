<?php

namespace Drupal\DrupalExtension\Compiler;

use Symfony\Component\DependencyInjection\ContainerBuilder,
    Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;

/**
 * Drupal\DrupalExtension container compilation pass.
 */
class DriverPass implements CompilerPassInterface {
  /**
   * Register Drupal drivers.
   */
  public function process(ContainerBuilder $container) {
    foreach ($container->findTaggedServiceIds('drupal.context.driver') as $id => $attributes) {
      // @todo Do something.
    }
  }
}
