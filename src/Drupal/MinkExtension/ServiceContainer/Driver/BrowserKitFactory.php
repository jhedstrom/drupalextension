<?php

namespace Drupal\MinkExtension\ServiceContainer\Driver;

use Behat\MinkExtension\ServiceContainer\Driver\BrowserKitFactory as BrowserKitFactoryOriginal;
use Behat\Mink\Driver\BrowserKitDriver;
use DrupalFinder\DrupalFinder;
use GuzzleHttp\Client;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\DependencyInjection\Definition;

class BrowserKitFactory extends BrowserKitFactoryOriginal
{
    /**
     * {@inheritdoc}
     */
    public function buildDriver(array $config): Definition
    {
        if (!class_exists(BrowserKitDriver::class)) {
            throw new \RuntimeException(
                'Install behat/mink-browserkit-driver in order to use the browserkit_http driver.'
            );
        }

        $drupalFinder = new DrupalFinder();
        $drupalFinder->locateRoot(getcwd());
        $drupalRoot = $drupalFinder->getDrupalRoot();
        require_once "$drupalRoot/core/tests/Drupal/Tests/DrupalTestBrowser.php";

        if (!class_exists('Drupal\Tests\DrupalTestBrowser')) {
            throw new \RuntimeException(
                'Class Drupal\Tests\DrupalTestBrowser not found'
            );
        }

        $guzzleRequestOptions = $config['guzzle_request_options'] ?? [
            'allow_redirects' => false,
            'cookies' => true,
        ];

        $guzzleClientService = new Definition(Client::class, [$guzzleRequestOptions]);
        $testBrowserService = (new Definition('Drupal\Tests\DrupalTestBrowser'))
          ->addMethodCall('setClient', [$guzzleClientService]);

        return new Definition(BrowserKitDriver::class, [
            $testBrowserService,
            '%mink.base_url%',
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function configure(ArrayNodeDefinition $builder): void
    {
        $builder->
            children()->
                arrayNode('guzzle_request_options')->
                  prototype('variable')->end()->
                info("Guzzle request options. See \\GuzzleHttp\\RequestOptions. Defaults to ['allow_redirects' => false, 'cookies' => true].")->
                end()->
            end();
    }
}
