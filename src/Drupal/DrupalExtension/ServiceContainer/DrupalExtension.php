<?php

namespace Drupal\DrupalExtension\ServiceContainer;

use Behat\Behat\Context\ServiceContainer\ContextExtension;
use Behat\Testwork\ServiceContainer\Extension as ExtensionInterface;
use Behat\Testwork\ServiceContainer\ExtensionManager;
use Behat\Testwork\ServiceContainer\ServiceProcessor;
use Drupal\DrupalExtension\Compiler\DriverPass;
use Drupal\DrupalExtension\Compiler\EventSubscriberPass;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Loader\FileLoader;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

class DrupalExtension implements ExtensionInterface {

  /**
   * Extension configuration ID.
   */
  const DRUPAL_ID = 'drupal';

  /**
   * Selectors handler ID.
   */
  const SELECTORS_HANDLER_ID = 'drupal.selectors_handler';

  /**
   * @var ServiceProcessor
   */
  private $processor;

  /**
   * Initializes compiler pass.
   *
   * @param null|ServiceProcessor $processor
   */
  public function __construct(ServiceProcessor $processor = null) {
    $this->processor = $processor ? : new ServiceProcessor();
  }

  /**
   * {@inheritDoc}
   */
  public function getConfigKey() {
    return self::DRUPAL_ID;
  }

  /**
   * {@inheritDoc}
   */
  public function initialize(ExtensionManager $extensionManager) {
  }

  /**
   * {@inheritDoc}
   */
  public function load(ContainerBuilder $container, array $config) {
    $loader = new YamlFileLoader($container, new FileLocator(__DIR__ . '/config'));
    $loader->load('services.yml');
    $container->setParameter('drupal.drupal.default_driver', $config['default_driver']);

    $this->loadParameters($container, $config);

    // Setup any drivers if requested.
    $this->loadBlackbox($loader, $config);
    $this->loadDrupal($loader, $container, $config);
    $this->loadDrush($loader, $container, $config);
  }

  /**
   * {@inheritDoc}
   */
  public function process(ContainerBuilder $container) {
    $this->processDriverPass($container);
    $this->processEventSubscriberPass($container);
    $this->processEnvironmentReaderPass($container);
    $this->processClassGenerator($container);
  }

  /**
   * {@inheritDoc}
   */
  public function configure(ArrayNodeDefinition $builder) {
    $builder->
      children()->
        scalarNode('default_driver')->
          defaultValue('blackbox')->
          info('Use "blackbox" to test remote site. See "api_driver" for easier integration.')->
        end()->
        scalarNode('api_driver')->
          defaultValue('drush')->
          info('Bootstraps drupal through "drupal8" or "drush".')->
        end()->
        scalarNode('drush_driver')->
          defaultValue('drush')->
        end()->
        arrayNode('region_map')->
          info("Targeting content in specific regions can be accomplished once those regions have been defined." . PHP_EOL
            . '  My region: "#css-selector"' . PHP_EOL
            . '  Content: "#main .region-content"'. PHP_EOL
            . '  Right sidebar: "#sidebar-second"'. PHP_EOL
          )->
          useAttributeAsKey('key')->
          prototype('variable')->
          end()->
        end()->
        arrayNode('text')->
          info(
              'Text strings, such as Log out or the Username field can be altered via behat.yml if they vary from the default values.' . PHP_EOL
            . '  log_out: "Sign out"' . PHP_EOL
            . '  log_in: "Sign in"' . PHP_EOL
            . '  password_field: "Enter your password"' . PHP_EOL
            . '  username_field: "Nickname"'
          )->
          addDefaultsIfNotSet()->
          children()->
            scalarNode('log_in')->
              defaultValue('Log in')->
            end()->
            scalarNode('log_out')->
              defaultValue('Log out')->
            end()->
            scalarNode('password_field')->
              defaultValue('Password')->
            end()->
            scalarNode('username_field')->
              defaultValue('Username')->
            end()->
          end()->
        end()->
        arrayNode('selectors')->
          addDefaultsIfNotSet()->
          children()->
            scalarNode('message_selector')->end()->
            scalarNode('error_message_selector')->end()->
            scalarNode('success_message_selector')->end()->
            scalarNode('warning_message_selector')->end()->
            scalarNode('login_form_selector')->
              defaultValue('form#user-login')->
            end()->
            scalarNode('logged_in_selector')->
              defaultValue('body.logged-in,body.user-logged-in')->
            end()->
          end()->
        end()->
        // Drupal drivers.
        arrayNode('blackbox')->
        end()->
        arrayNode('drupal')->
          children()->
            scalarNode('drupal_root')->end()->
          end()->
        end()->
        arrayNode('drush')->
          children()->
            scalarNode('alias')->end()->
            scalarNode('binary')->defaultValue('drush')->end()->
            scalarNode('root')->end()->
            scalarNode('global_options')->end()->
          end()->
        end()->
        // Subcontext paths.
        arrayNode('subcontexts')->
          info(
              'The Drupal Extension is capable of discovering additional step-definitions provided by subcontexts.' . PHP_EOL
            . 'Module authors can provide these in files following the naming convention of foo.behat.inc. Once that module is enabled, the Drupal Extension will load these.' . PHP_EOL
            . PHP_EOL
            . 'Additional subcontexts can be loaded by either placing them in the bootstrap directory (typically features/bootstrap) or by adding them to behat.yml.'
          )->
          addDefaultsIfNotSet()->
          children()->
            arrayNode('paths')->
              info(
                '- /path/to/additional/subcontexts' . PHP_EOL
              . '- /another/path'
              )->
              useAttributeAsKey('key')->
              prototype('variable')->end()->
            end()->
            scalarNode('autoload')->
              defaultValue(TRUE)->
            end()->
          end()->
        end()->
      end()->
    end();
  }

  /**
   * Load test parameters.
   */
  private function loadParameters(ContainerBuilder $container, array $config) {
    // Store config in parameters array to be passed into the DrupalContext.
    $drupal_parameters = array();
    foreach ($config as $key => $value) {
      $drupal_parameters[$key] = $value;
    }
    $container->setParameter('drupal.parameters', $drupal_parameters);

    $container->setParameter('drupal.region_map', $config['region_map']);
  }

  /**
   * Load the blackbox driver.
   */
  private function loadBlackBox(FileLoader $loader, array $config) {
    // Always include the blackbox driver.
    $loader->load('drivers/blackbox.yml');
  }

  /**
   * Load the Drupal driver.
   */
  private function loadDrupal(FileLoader $loader, ContainerBuilder $container, array $config) {
    if (isset($config['drupal'])) {
      $loader->load('drivers/drupal.yml');
      $container->setParameter('drupal.driver.drupal.drupal_root', $config['drupal']['drupal_root']);
    }
  }

  /**
   * Load the Drush driver.
   */
  private function loadDrush(FileLoader $loader, ContainerBuilder $container, array $config) {
    if (isset($config['drush'])) {
      $loader->load('drivers/drush.yml');
      if (!isset($config['drush']['alias']) && !isset($config['drush']['root'])) {
        throw new \RuntimeException('Drush `alias` or `root` path is required for the Drush driver.');
      }
      $config['drush']['alias'] = isset($config['drush']['alias']) ? $config['drush']['alias'] : FALSE;
      $container->setParameter('drupal.driver.drush.alias', $config['drush']['alias']);

      $config['drush']['binary'] = isset($config['drush']['binary']) ? $config['drush']['binary'] : 'drush';
      $container->setParameter('drupal.driver.drush.binary', $config['drush']['binary']);

      $config['drush']['root'] = isset($config['drush']['root']) ? $config['drush']['root'] : FALSE;
      $container->setParameter('drupal.driver.drush.root', $config['drush']['root']);

      // Set global arguments.
      $this->setDrushOptions($container, $config);
    }
  }

  /**
   * Set global drush arguments.
   */
  private function setDrushOptions(ContainerBuilder $container, array $config) {
    if (isset($config['drush']['global_options'])) {
      $definition = $container->getDefinition('drupal.driver.drush');
      $definition->addMethodCall('setArguments', array($config['drush']['global_options']));
    }
  }

  /**
   * Process the Driver Pass.
   */
  private function processDriverPass(ContainerBuilder $container) {
    $driverPass = new DriverPass();
    $driverPass->process($container);
  }

  /**
   * Process the Event Subscriber Pass.
   */
  private function processEventSubscriberPass(ContainerBuilder $container) {
    $eventSubscriberPass = new EventSubscriberPass();
    $eventSubscriberPass->process($container);
  }

  /**
   * Process the Environment Reader pass.
   */
  private function processEnvironmentReaderPass(ContainerBuilder $container) {
    // Register Behat context readers.
    $references = $this->processor->findAndSortTaggedServices($container, ContextExtension::READER_TAG);
    $definition = $container->getDefinition('drupal.context.environment.reader');

    foreach ($references as $reference) {
      $definition->addMethodCall('registerContextReader', array($reference));
    }
  }

  /**
   * Switch to custom class generator.
   */
  private function processClassGenerator(ContainerBuilder $container) {
    $definition = new Definition('Drupal\DrupalExtension\Context\ContextClass\ClassGenerator');
    $container->setDefinition(ContextExtension::CLASS_GENERATOR_TAG . '.simple', $definition);
  }
}
