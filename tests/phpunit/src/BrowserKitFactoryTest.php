<?php

declare(strict_types=1);

namespace Drupal\DrupalExtension\Tests;

use Behat\Mink\Driver\BrowserKitDriver;
use Drupal\MinkExtension\ServiceContainer\Driver\BrowserKitFactory;
use GuzzleHttp\Client;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\DependencyInjection\Definition;

#[CoversClass(BrowserKitFactory::class)]
class BrowserKitFactoryTest extends TestCase
{

    public function testDriverName(): void
    {
        $factory = new BrowserKitFactory();
        $this->assertSame('browserkit_http', $factory->getDriverName());
    }

    #[DataProvider('dataProviderConfigure')]
    public function testConfigure(array $input, mixed $expected): void
    {
        $builder = new ArrayNodeDefinition('test');
        $factory = new BrowserKitFactory();
        $factory->configure($builder);

        $tree = $builder->getNode(true);
        $config = $tree->finalize($tree->normalize($input));

        $this->assertSame($expected, $config['guzzle_request_options']);
    }

    public static function dataProviderConfigure(): \Iterator
    {
        yield 'default is empty' => [[], []];
        yield 'single option' => [
            ['guzzle_request_options' => ['verify' => false]],
            ['verify' => false],
        ];
        yield 'multiple options' => [
            ['guzzle_request_options' => ['allow_redirects' => false, 'cookies' => true, 'timeout' => 30]],
            ['allow_redirects' => false, 'cookies' => true, 'timeout' => 30],
        ];
    }

    #[DataProvider('dataProviderBuildDriver')]
    public function testBuildDriver(array $config, array $expected_guzzle_options): void
    {
        $factory = $this->createFactoryWithCwd();
        $definition = $factory->buildDriver($config);

        $this->assertInstanceOf(Definition::class, $definition);
        $this->assertSame(BrowserKitDriver::class, $definition->getClass());

        $args = $definition->getArguments();
        $this->assertCount(2, $args);
        $this->assertSame('%mink.base_url%', $args[1]);

        // Verify the test browser service definition.
        $testBrowser = $args[0];
        $this->assertInstanceOf(Definition::class, $testBrowser);
        $this->assertSame('Drupal\Tests\DrupalTestBrowser', $testBrowser->getClass());

        // Verify the Guzzle client service definition.
        $methodCalls = $testBrowser->getMethodCalls();
        $this->assertCount(1, $methodCalls);
        $this->assertSame('setClient', $methodCalls[0][0]);

        $guzzleDefinition = $methodCalls[0][1][0];
        $this->assertInstanceOf(Definition::class, $guzzleDefinition);
        $this->assertSame(Client::class, $guzzleDefinition->getClass());
        $this->assertSame($expected_guzzle_options, $guzzleDefinition->getArguments()[0]);
    }

    public static function dataProviderBuildDriver(): \Iterator
    {
        yield 'default guzzle options' => [
            [],
            ['allow_redirects' => false, 'cookies' => true],
        ];
        yield 'custom guzzle options' => [
            ['guzzle_request_options' => ['verify' => false, 'timeout' => 30]],
            ['verify' => false, 'timeout' => 30],
        ];
    }

    private function createFactoryWithCwd(): BrowserKitFactory
    {
        $factory = $this->getMockBuilder(BrowserKitFactory::class)
            ->onlyMethods(['getCwd'])
            ->getMock();
        $factory->method('getCwd')->willReturn('/var/www/html/build');
        return $factory;
    }
}
