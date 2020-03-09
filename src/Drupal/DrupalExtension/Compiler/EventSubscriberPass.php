<?php

namespace Drupal\DrupalExtension\Compiler;

use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;

/**
 * Event subscribers pass - registers all available event subscribers.
 */
class EventSubscriberPass implements CompilerPassInterface
{
  /**
   * Processes container.
   *
   * @param ContainerBuilder $container
   */
    public function process(ContainerBuilder $container)
    {
        if (!$container->hasDefinition('drupal.event_dispatcher')) {
            return;
        }
        $dispatcherDefinition = $container->getDefinition('drupal.event_dispatcher');

        foreach ($container->findTaggedServiceIds('drupal.event_subscriber') as $id => $attributes) {
            foreach ($attributes as $attribute) {
                $priority = isset($attribute['priority']) ? intval($attribute['priority']) : 0;
                $dispatcherDefinition->addMethodCall(
                    'addSubscriber',
                    [new Reference($id), $priority]
                );
            }
        }
    }
}
