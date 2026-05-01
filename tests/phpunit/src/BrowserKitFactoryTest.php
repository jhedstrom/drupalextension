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

/**
 * Tests the BrowserKitFactory class.
 */
#[CoversClass(BrowserKitFactory::class)]
class BrowserKitFactoryTest extends TestCase {

  /**
   * Tests the driver name.
   */
  public function testDriverName(): void {
    $factory = new BrowserKitFactory();
    $this->assertSame('browserkit_http', $factory->getDriverName());
  }

  /**
   * Tests the configure method.
   *
   * @param array<string, mixed> $input
   *   The configuration input.
   * @param array<string, mixed> $expected
   *   The expected guzzle request options.
   */
  #[DataProvider('dataProviderConfigure')]
  public function testConfigure(array $input, array $expected): void {
    $builder = new ArrayNodeDefinition('test');
    $factory = new BrowserKitFactory();
    $factory->configure($builder);

    $tree = $builder->getNode(TRUE);
    $config = $tree->finalize($tree->normalize($input));

    $this->assertSame($expected, $config['guzzle_request_options']);
  }

  /**
   * Provides data for testConfigure().
   */
  public static function dataProviderConfigure(): \Iterator {
    yield 'default is empty' => [[], []];
    yield 'single option' => [
          ['guzzle_request_options' => ['verify' => FALSE]],
          ['verify' => FALSE],
    ];
    yield 'multiple options' => [
          ['guzzle_request_options' => ['allow_redirects' => FALSE, 'cookies' => TRUE, 'timeout' => 30]],
          ['allow_redirects' => FALSE, 'cookies' => TRUE, 'timeout' => 30],
    ];
  }

  /**
   * Tests the buildDriver method.
   *
   * @param array<string, mixed> $config
   *   Driver configuration.
   * @param array<string, mixed> $expected_guzzle_options
   *   Expected guzzle options.
   */
  #[DataProvider('dataProviderBuildDriver')]
  public function testBuildDriver(array $config, array $expected_guzzle_options): void {
    $factory = $this->createFactoryWithCwd();
    $definition = $factory->buildDriver($config);

    $this->assertInstanceOf(Definition::class, $definition);
    $this->assertSame(BrowserKitDriver::class, $definition->getClass());

    $args = $definition->getArguments();
    $this->assertCount(2, $args);
    $this->assertSame('%mink.base_url%', $args[1]);

    // Verify the test browser service definition.
    $test_browser = $args[0];
    $this->assertInstanceOf(Definition::class, $test_browser);
    $this->assertSame('Drupal\Tests\DrupalTestBrowser', $test_browser->getClass());

    // Verify the Guzzle client service definition.
    $method_calls = $test_browser->getMethodCalls();
    $this->assertCount(1, $method_calls);
    $this->assertSame('setClient', $method_calls[0][0]);

    $guzzle_definition = $method_calls[0][1][0];
    $this->assertInstanceOf(Definition::class, $guzzle_definition);
    $this->assertSame(Client::class, $guzzle_definition->getClass());
    $this->assertSame($expected_guzzle_options, $guzzle_definition->getArguments()[0]);
  }

  /**
   * Provides data for testBuildDriver().
   */
  public static function dataProviderBuildDriver(): \Iterator {
    yield 'default guzzle options' => [
          [],
          ['allow_redirects' => FALSE, 'cookies' => TRUE],
    ];
    yield 'custom guzzle options' => [
          ['guzzle_request_options' => ['verify' => FALSE, 'timeout' => 30]],
          ['verify' => FALSE, 'timeout' => 30],
    ];
  }

  /**
   * Creates a factory with a mocked working directory.
   */
  private function createFactoryWithCwd(): BrowserKitFactory {
    $factory = $this->getMockBuilder(BrowserKitFactory::class)
      ->onlyMethods(['getCwd'])
      ->getMock();
    $factory->method('getCwd')->willReturn('/app/build');
    return $factory;
  }

}
