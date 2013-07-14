<?php<?php

namespace Drupal\DrupalExtension\Compiler;

use Symfony\Component\DependencyInjection\Reference,
    Symfony\Component\DependencyInjection\ContainerBuilder,
    Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;

/**
 * Context loaders pass - registers all available context loaders.
 */
class ContextLoadersPass implements CompilerPassInterface {
    /**
     * Processes container.
     *
     * @param ContainerBuilder $container
     */
    public function process(ContainerBuilder $container) {
        if (!$container->hasDefinition('drupal.')) {
            return;
        }
        $readerDefinition = $container->getDefinition('behat.context.reader');

        foreach ($container->findTaggedServiceIds('behat.context.loader') as $id => $attributes) {
            $readerDefinition->addMethodCall('addLoader', array(new Reference($id)));
        }
    }
}
