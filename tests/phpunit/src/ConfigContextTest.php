<?php

declare(strict_types=1);

namespace Drupal\DrupalExtension\Tests;

use PHPUnit\Framework\MockObject\MockObject;
use Drupal\Driver\DriverInterface;
use Drupal\DrupalDriverManagerInterface;
use Drupal\DrupalExtension\Context\ConfigContext;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(ConfigContext::class)]
class ConfigContextTest extends TestCase
{

    protected ConfigContext $context;

    protected MockObject $driver;

    protected function setUp(): void
    {
        $this->context = new ConfigContext();
        $this->driver = $this->createMock(DriverInterface::class);
        $drupal = $this->createMock(DrupalDriverManagerInterface::class);
        $drupal->method('getDriver')->willReturn($this->driver);
        $this->context->setDrupal($drupal);
    }

    #[DataProvider('dataProviderSetConfigBackup')]
    public function testSetConfigBackup(array $operations, array $expected_backup): void
    {
        $getReturns = array_column($operations, 'original');
        $this->driver->method('configGet')->willReturnOnConsecutiveCalls(...$getReturns);
        $this->driver->method('configSet');

        foreach ($operations as $operation) {
            $this->context->setConfig($operation['name'], $operation['key'], $operation['new_value']);
        }

        $config = new \ReflectionProperty(ConfigContext::class, 'config');
        $stored = $config->getValue($this->context);

        foreach ($expected_backup as $name => $keys) {
            foreach ($keys as $key => $value) {
                $this->assertSame($value, $stored[$name][$key]);
            }
        }
    }

    public static function dataProviderSetConfigBackup(): \Iterator
    {
        yield 'single key backup' => [
            [['name' => 'system.site', 'key' => 'name', 'original' => 'Original', 'new_value' => 'New']],
            ['system.site' => ['name' => 'Original']],
        ];
        yield 'does not overwrite backup on second set' => [
            [
                ['name' => 'system.site', 'key' => 'name', 'original' => 'Original', 'new_value' => 'First'],
                ['name' => 'system.site', 'key' => 'name', 'original' => 'First', 'new_value' => 'Second'],
            ],
            ['system.site' => ['name' => 'Original']],
        ];
        yield 'tracks multiple keys in same config' => [
            [
                ['name' => 'system.site', 'key' => 'name', 'original' => 'Original Name', 'new_value' => 'New Name'],
                ['name' => 'system.site', 'key' => 'slogan', 'original' => 'Original Slogan', 'new_value' => 'New Slogan'],
            ],
            ['system.site' => ['name' => 'Original Name', 'slogan' => 'Original Slogan']],
        ];
        yield 'tracks multiple config objects' => [
            [
                ['name' => 'system.site', 'key' => 'name', 'original' => 'Site', 'new_value' => 'New Site'],
                ['name' => 'system.performance', 'key' => 'cache', 'original' => '1', 'new_value' => '0'],
            ],
            ['system.site' => ['name' => 'Site'], 'system.performance' => ['cache' => '1']],
        ];
    }

    public function testCleanConfigRestoresAllValues(): void
    {
        $this->driver->method('configGet')->willReturn('Original');

        $setArgs = [];
        $this->driver->method('configSet')
            ->willReturnCallback(function (string $name, string $key, mixed $value) use (&$setArgs): void {
                $setArgs[] = [$name, $key, $value];
            });

        $this->context->setConfig('system.site', 'name', 'New Name');
        $this->context->cleanConfig();

        $restoreCall = end($setArgs);
        $this->assertSame(['system.site', 'name', 'Original'], $restoreCall);

        $config = new \ReflectionProperty(ConfigContext::class, 'config');
        $this->assertSame([], $config->getValue($this->context));
    }

    public function testSetBasicConfigDelegatesToSetConfig(): void
    {
        $this->driver->method('configGet')->willReturn('old');
        $this->driver->expects($this->once())
            ->method('configSet')
            ->with('system.site', 'name', 'New');

        $this->context->setBasicConfig('system.site', 'name', 'New');
    }
}
