<?php

declare(strict_types=1);

namespace Drupal\MinkExtension\ServiceContainer;

use Behat\MinkExtension\ServiceContainer\MinkExtension as BaseMinkExtension;
use Drupal\MinkExtension\ServiceContainer\Driver\BrowserKitFactory;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;

/**
 * Drupal Mink extension with additional browser driver support.
 */
class MinkExtension extends BaseMinkExtension {

  /**
   * Default wait time for AJAX to finish (in seconds).
   *
   * @var int
   */
  const AJAX_TIMEOUT = 5;

  /**
   * {@inheritdoc}
   */
  public function __construct() {
    parent::__construct();
    $this->registerDriverFactory(new BrowserKitFactory());
  }

  /**
   * {@inheritdoc}
   */
  public function configure(ArrayNodeDefinition $builder): void {
    parent::configure($builder);

    // Add extended options.
    // @formatter:off
    // phpcs:disable
    $builder
      ->children()
        ->scalarNode('ajax_timeout')
          ->defaultValue(static::AJAX_TIMEOUT)
          ->info(sprintf('Change the maximum time to wait for AJAX calls to complete. Defaults to %s seconds.', static::AJAX_TIMEOUT))
        ->end()
        ->arrayNode('selectors')
          ->info(
            'CSS selectors consumed by Mink-based contexts, grouped by concern. Replaces the four flat message selectors previously configured under "Drupal\\DrupalExtension.selectors:".' . PHP_EOL
            . '  messages:' . PHP_EOL
            . '    default: ".messages"' . PHP_EOL
            . '    error:   ".messages--error"' . PHP_EOL
            . '    success: ".messages--status"' . PHP_EOL
            . '    warning: ".messages--warning"'
          )
          ->ignoreExtraKeys(FALSE)
          ->children()
            ->arrayNode('messages')
              ->ignoreExtraKeys(FALSE)
              ->children()
                ->scalarNode('default')->end()
                ->scalarNode('error')->end()
                ->scalarNode('success')->end()
                ->scalarNode('warning')->end()
              ->end()
            ->end()
          ->end()
        ->end()
      ->end();
    // phpcs:enable
    // @formatter:on
  }

}
