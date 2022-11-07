<?php

namespace Drupal\MinkExtension\ServiceContainer\Driver;

use Behat\MinkExtension\ServiceContainer\Driver\BrowserKitFactory as BrowserKitFactoryOriginal;
use Behat\Mink\Driver\BrowserKitDriver;
use DrupalFinder\DrupalFinder;
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
        $drupalFinder->locateRoot(__DIR__);
        $drupalRoot = $drupalFinder->getDrupalRoot();
        $vendorDir = $drupalFinder->getVendorDir();

        /** @var \Composer\Autoload\ClassLoader $loader */
        $loader = require "$vendorDir/autoload.php";
        $loader->addClassMap(
            ["$drupalRoot/core/tests/Drupal/Tests/DrupalTestBrowser.php"]
        );

        if (!class_exists('Drupal\Tests\DrupalTestBrowser')) {
            throw new \RuntimeException(
                'Class Drupal\Tests\DrupalTestBrowser not found'
            );
        }

        return new Definition(BrowserKitDriver::class, [
            new Definition('Drupal\Tests\DrupalTestBrowser'),
            '%mink.base_url%',
        ]);
    }
}
