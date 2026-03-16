<?php

declare(strict_types=1);

namespace Drupal\DrupalExtension\ServiceContainer;

use Behat\Behat\Context\ServiceContainer\ContextExtension;
use Behat\Testwork\ServiceContainer\Extension as ExtensionInterface;
use Behat\Testwork\ServiceContainer\ExtensionManager;
use Behat\Testwork\ServiceContainer\ServiceProcessor;
use Drupal\DrupalExtension\Compiler\DriverPass;
use Drupal\DrupalExtension\Compiler\EventSubscriberPass;
use Drupal\DrupalExtension\Context\ContextClass\ClassGenerator;
use Behat\Mink\Element\DocumentElement as MinkDocumentElement;
use Drupal\DrupalExtension\Element\DocumentElement;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Loader\FileLoader;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

/**
 * Drupal extension for Behat providing step definitions and driver management.
 */
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
   * Service processor for finding and sorting tagged services.
   */
  private readonly ServiceProcessor $serviceProcessor;

  /**
   * Initializes compiler pass.
   */
  public function __construct(?ServiceProcessor $processor = NULL) {
    $this->serviceProcessor = $processor ?: new ServiceProcessor();
  }

  /**
   * {@inheritdoc}
   */
  public function getConfigKey() {
    return self::DRUPAL_ID;
  }

  /**
   * {@inheritdoc}
   */
  public function initialize(ExtensionManager $extensionManager) {
  }

  /**
   * {@inheritdoc}
   */
  public function load(ContainerBuilder $container, array $config): void {
    // Workaround a bug in BrowserKitDriver that wrongly considers as text
    // of the page, pieces of texts inside the <head> section.
    // @see https://github.com/minkphp/MinkBrowserKitDriver/issues/153
    // @see https://www.drupal.org/project/drupal/issues/3175718
    class_alias(DocumentElement::class, MinkDocumentElement::class, TRUE);

    $loader = new YamlFileLoader($container, new FileLocator(__DIR__ . '/config'));
    $loader->load('services.yml');
    $container->setParameter('drupal.drupal.default_driver', $config['default_driver']);

    $this->loadParameters($container, $config);

    // Setup any drivers if requested.
    $this->loadBlackbox($loader);
    $this->loadDrupal($loader, $container, $config);
    $this->loadDrush($loader, $container, $config);
  }

  /**
   * {@inheritdoc}
   */
  public function process(ContainerBuilder $container): void {
    $this->processDriverPass($container);
    $this->processEventSubscriberPass($container);
    $this->processEnvironmentReaderPass($container);
    $this->processClassGenerator($container);
  }

  /**
   * {@inheritdoc}
   */
  public function configure(ArrayNodeDefinition $builder): void {
    // @formatter:off
    // phpcs:disable
    $builder
      ->children()
        ->scalarNode('default_driver')
          ->defaultValue('blackbox')
          ->info('Use "blackbox" to test remote site. See "api_driver" for easier integration.')
        ->end()
        ->scalarNode('api_driver')
          ->defaultValue('drush')
          ->info('Bootstraps drupal through "drupal8" or "drush".')
        ->end()
        ->scalarNode('drush_driver')
          ->defaultValue('drush')
        ->end()
        ->arrayNode('region_map')
          ->info("Targeting content in specific regions can be accomplished once those regions have been defined." . PHP_EOL
            . '  My region: "#css-selector"' . PHP_EOL
            . '  Content: "#main .region-content"' . PHP_EOL
            . '  Right sidebar: "#sidebar-second"' . PHP_EOL)
          ->useAttributeAsKey('key')
          ->prototype('variable')->end()
        ->end()
        ->arrayNode('text')
          ->info(
            'Text strings, such as Log out or the Username field can be altered via behat.yml if they vary from the default values.' . PHP_EOL
            . '  login_url: "/user"' . PHP_EOL
            . '  logout_url: "/user/logout"' . PHP_EOL
            . '  logout_confirm_url: "/user/logout/confirm"' . PHP_EOL
            . '  log_out: "Sign out"' . PHP_EOL
            . '  log_in: "Sign in"' . PHP_EOL
            . '  password_field: "Enter your password"' . PHP_EOL
            . '  username_field: "Nickname"'
          )
          ->ignoreExtraKeys(FALSE)
          ->addDefaultsIfNotSet()
          ->children()
            ->scalarNode('login_url')
              ->defaultValue('/user')
            ->end()
            ->scalarNode('logout_url')
              ->defaultValue('/user/logout')
            ->end()
            ->scalarNode('logout_confirm_url')
              ->defaultValue('/user/logout/confirm')
            ->end()
            ->scalarNode('log_in')
              ->defaultValue('Log in')
            ->end()
            ->scalarNode('log_out')
              ->defaultValue('Log out')
            ->end()
            ->scalarNode('password_field')
              ->defaultValue('Password')
            ->end()
            ->scalarNode('username_field')
              ->defaultValue('Username')
            ->end()
          ->end()
        ->end()
        ->arrayNode('selectors')
          ->ignoreExtraKeys(FALSE)
          ->addDefaultsIfNotSet()
          ->children()
            ->scalarNode('message_selector')->end()
            ->scalarNode('error_message_selector')->end()
            ->scalarNode('success_message_selector')->end()
            ->scalarNode('warning_message_selector')->end()
            ->scalarNode('login_form_selector')
              ->defaultValue('form#user-login,form#user-login-form')
            ->end()
            ->scalarNode('logged_in_selector')
              ->defaultValue('body.logged-in,body.user-logged-in')
            ->end()
          ->end()
        ->end()
        // Drupal drivers.
        ->arrayNode('blackbox')->end()
        ->arrayNode('drupal')
          ->children()
            ->scalarNode('drupal_root')->end()
          ->end()
        ->end()
        ->arrayNode('drush')
          ->children()
            ->scalarNode('alias')->end()
            ->scalarNode('binary')->defaultValue('drush')->end()
            ->scalarNode('root')->end()
            ->scalarNode('global_options')->end()
          ->end()
        ->end()
        // Subcontext paths.
        ->arrayNode('subcontexts')
          ->info(
            'The Drupal Extension is capable of discovering additional step-definitions provided by subcontexts.' . PHP_EOL
            . 'Module authors can provide these in files following the naming convention of foo.behat.inc. Once that module is enabled, the Drupal Extension will load these.' . PHP_EOL
            . PHP_EOL
            . 'Additional subcontexts can be loaded by either placing them in the bootstrap directory (typically features/bootstrap) or by adding them to behat.yml.'
          )
          ->addDefaultsIfNotSet()
          ->children()
            ->arrayNode('paths')
              ->info(
                '- /path/to/additional/subcontexts' . PHP_EOL
                . '- /another/path'
              )
              ->useAttributeAsKey('key')
              ->prototype('variable')->end()
            ->end()
            ->scalarNode('autoload')
              ->defaultValue(TRUE)
            ->end()
          ->end()
        ->end()
      ->end()
    ->end();
    // phpcs:enable
    // @formatter:on
  }

  /**
   * Load test parameters.
   */
  protected function loadParameters(ContainerBuilder $container, array $config): void {
    $container->setParameter('drupal.parameters', $config);
    $container->setParameter('drupal.region_map', $config['region_map']);
  }

  /**
   * Load the blackbox driver.
   */
  protected function loadBlackBox(FileLoader $loader): void {
    // Always include the blackbox driver.
    $loader->load('drivers/blackbox.yml');
  }

  /**
   * Load the Drupal driver.
   */
  protected function loadDrupal(FileLoader $loader, ContainerBuilder $container, array $config): void {
    if (isset($config['drupal'])) {
      $loader->load('drivers/drupal.yml');
      $container->setParameter('drupal.driver.drupal.drupal_root', $config['drupal']['drupal_root']);
    }
  }

  /**
   * Load the Drush driver.
   */
  protected function loadDrush(FileLoader $loader, ContainerBuilder $container, array $config): void {
    if (isset($config['drush'])) {
      $loader->load('drivers/drush.yml');
      if (!isset($config['drush']['alias']) && !isset($config['drush']['root'])) {
        throw new \RuntimeException('Drush `alias` or `root` path is required for the Drush driver.');
      }
      $config['drush']['alias'] ??= FALSE;
      $container->setParameter('drupal.driver.drush.alias', $config['drush']['alias']);

      $config['drush']['binary'] ??= 'drush';
      $container->setParameter('drupal.driver.drush.binary', $config['drush']['binary']);

      $config['drush']['root'] ??= FALSE;
      $container->setParameter('drupal.driver.drush.root', $config['drush']['root']);

      // Set global arguments.
      $this->setDrushOptions($container, $config);
    }
  }

  /**
   * Set global drush arguments.
   */
  protected function setDrushOptions(ContainerBuilder $container, array $config): void {
    if (isset($config['drush']['global_options'])) {
      $definition = $container->getDefinition('drupal.driver.drush');
      $definition->addMethodCall('setArguments', [$config['drush']['global_options']]);
    }
  }

  /**
   * Process the Driver Pass.
   */
  protected function processDriverPass(ContainerBuilder $container): void {
    $driverPass = new DriverPass();
    $driverPass->process($container);
  }

  /**
   * Process the Event Subscriber Pass.
   */
  protected function processEventSubscriberPass(ContainerBuilder $container): void {
    $eventSubscriberPass = new EventSubscriberPass();
    $eventSubscriberPass->process($container);
  }

  /**
   * Process the Environment Reader pass.
   */
  protected function processEnvironmentReaderPass(ContainerBuilder $container): void {
    // Register Behat context readers.
    $references = $this->serviceProcessor->findAndSortTaggedServices($container, ContextExtension::READER_TAG);
    $definition = $container->getDefinition('drupal.context.environment.reader');

    foreach ($references as $reference) {
      $definition->addMethodCall('registerContextReader', [$reference]);
    }
  }

  /**
   * Switch to custom class generator.
   */
  protected function processClassGenerator(ContainerBuilder $container): void {
    $definition = new Definition(ClassGenerator::class);
    $container->setDefinition(ContextExtension::CLASS_GENERATOR_TAG . '.simple', $definition);
  }

}
