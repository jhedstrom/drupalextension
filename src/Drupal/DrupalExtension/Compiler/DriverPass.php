<?php

namespace Drupal\DrupalExtension\Compiler;

use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;

/**
 * Drupal\DrupalExtension container compilation pass.
 */
class DriverPass implements CompilerPassInterface
{
  /**
   * Register Drupal drivers.
   */
    public function process(ContainerBuilder $container)
    {
        if (!$container->hasDefinition('drupal.drupal')) {
            return;
        }

        $drupalDefinition = $container->getDefinition('drupal.drupal');
        foreach ($container->findTaggedServiceIds('drupal.driver') as $id => $attributes) {
            foreach ($attributes as $attribute) {
                if (isset($attribute['alias']) && $name = $attribute['alias']) {
                    $drupalDefinition->addMethodCall(
                        'registerDriver',
                        [$name, new Reference($id)]
                    );
                }
            }

            // If this is Drupal Driver, then a core controller needs to be
            // instantiated as well.
            if ('drupal.driver.drupal' === $id) {
                $drupalDriverDefinition = $container->getDefinition($id);
                $availableCores = [];
                foreach ($container->findTaggedServiceIds('drupal.core') as $coreId => $coreAttributes) {
                    foreach ($coreAttributes as $attribute) {
                        if (isset($attribute['alias']) && $name = $attribute['alias']) {
                            $availableCores[$name] = $container->getDefinition($coreId);
                        }
                    }
                }
                $drupalDriverDefinition->addMethodCall(
                    'setCore',
                    [$availableCores]
                );
            }
        }

        $drupalDefinition->addMethodCall(
            'setDefaultDriverName',
            [$container->getParameter('drupal.drupal.default_driver')]
        );
    }
}
