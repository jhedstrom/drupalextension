<?php

declare(strict_types=1);

namespace Drupal\MinkExtension\ServiceContainer\Driver;

use Behat\MinkExtension\ServiceContainer\Driver\BrowserKitFactory as BrowserKitFactoryOriginal;
use Behat\Mink\Driver\BrowserKitDriver;
use DrupalFinder\DrupalFinder;
use GuzzleHttp\Client;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\DependencyInjection\Definition;

/**
 * BrowserKit driver factory using Drupal's test browser.
 */
class BrowserKitFactory extends BrowserKitFactoryOriginal {

  /**
   * {@inheritdoc}
   *
   * @param array<string, mixed> $config
   *   Driver configuration.
   */
  public function buildDriver(array $config): Definition {
    if (!class_exists(BrowserKitDriver::class)) {
      // @codeCoverageIgnoreStart
      throw new \RuntimeException(
            'Install behat/mink-browserkit-driver in order to use the browserkit_http driver.'
        );
      // @codeCoverageIgnoreEnd
    }

    $drupal_finder = new DrupalFinder();
    $drupal_finder->locateRoot($this->getCwd());
    $drupal_root = $drupal_finder->getDrupalRoot();
    require_once $drupal_root . '/core/tests/Drupal/Tests/DrupalTestBrowser.php';

    if (!class_exists('Drupal\Tests\DrupalTestBrowser')) {
      // @codeCoverageIgnoreStart
      throw new \RuntimeException(
            'Class Drupal\Tests\DrupalTestBrowser not found'
        );
      // @codeCoverageIgnoreEnd
    }

    $guzzle_request_options = $config['guzzle_request_options'] ?? [
      'allow_redirects' => FALSE,
      'cookies' => TRUE,
    ];

    $guzzle_client_service = new Definition(Client::class, [$guzzle_request_options]);
    $test_browser_service = (new Definition('Drupal\Tests\DrupalTestBrowser'))
      ->addMethodCall('setClient', [$guzzle_client_service]);

    return new Definition(BrowserKitDriver::class, [
      $test_browser_service,
      '%mink.base_url%',
    ]);
  }

  /**
   * Returns the current working directory.
   *
   * @codeCoverageIgnore
   */
  protected function getCwd(): string {
    return (string) getcwd();
  }

  /**
   * {@inheritdoc}
   */
  public function configure(ArrayNodeDefinition $builder): void {
    // @formatter:off
    // phpcs:disable
    $builder
      ->children()
        ->arrayNode('guzzle_request_options')
          ->prototype('variable')->end()
          ->info("Guzzle request options. See \\GuzzleHttp\\RequestOptions. Defaults to ['allow_redirects' => false, 'cookies' => true].")
        ->end()
      ->end();
    // phpcs:enable
    // @formatter:on
  }

}
