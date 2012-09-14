<?php

namespace Drupal\DrupalExtension;

use Symfony\Component\DependencyInjection\ContainerBuilder,
    Symfony\Component\DependencyInjection\Loader\YamlFileLoader,
    Symfony\Component\Config\FileLocator,
    Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;

use Behat\Behat\Extension\ExtensionInterface;

class Extension implements ExtensionInterface {

  /**
   * Loads a specific configuration.
   *
   * @param array $config
   *   Extension configuration (from behat.yml).
   * @param ContainerBuilder $container
   *   ContainerBuilder instance.
   */
  public function load(array $config, ContainerBuilder $container) {
    $loader = new YamlFileLoader($container, new FileLocator(__DIR__ . '/config'));
    $loader->load('services.yml');
    $container->setParameter('drupal.basic_auth', $config['basic_auth']);
    $container->setParameter('drupal.drush_alias', $config['drush_alias']);

    // Store config in parameters array to be passed into the DrupalContext.
    $drupal_parameters = array();
    foreach ($config as $key => $value) {
      $drupal_parameters[$key] = $value;
    }
    $container->setParameter('drupal.parameters', $drupal_parameters);

    $container->setParameter('drupal.region_map', $config['region_map']);

    // Setup any drivers if requested.
    if (isset($config['drush'])) {
      $loader->load('drivers/drush.yml');
    }
  }

  /**
   * Setup configuration for this extension.
   *
   * @param ArrayNodeDefinition $builder
   *   ArrayNodeDefinition instance.
   */
  public function getConfig(ArrayNodeDefinition $builder) {
    $builder->
      children()->
        arrayNode('basic_auth')->
          useAttributeAsKey('key')->
          prototype('variable')->end()->
        end()->
        scalarNode('drush_alias')->
          defaultNull()->
        end()->
        arrayNode('region_map')->
          useAttributeAsKey('key')->
          prototype('variable')->end()->
        end()->
        // Drupal drivers.
        arrayNode('drush')->
          children()->
            scalarNode('alias')->
          end()->
        end()->
      end()->
    end();
  }

  /**
   * Returns compiler passes used by mink extension.
   *
   * @return array
   */
  public function getCompilerPasses() {
    return array(
      new Compiler\DriverPass(),
    );
  }
}
