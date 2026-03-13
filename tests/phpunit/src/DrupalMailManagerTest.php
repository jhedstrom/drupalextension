<?php

declare(strict_types=1);

namespace Drupal\DrupalExtension\Tests;

use Drupal\Driver\DriverInterface;
use Drupal\DrupalMailManager;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(DrupalMailManager::class)]
class DrupalMailManagerTest extends TestCase
{

    #[DataProvider('dataProviderDriverDelegation')]
    public function testDriverDelegation(string $method, string $driver_method, array $extra_driver_methods = []): void
    {
        $driver = $this->createMock(DriverInterface::class);
        $driver->expects($this->once())->method($driver_method);
        foreach ($extra_driver_methods as $extraDriverMethod) {
            $driver->expects($this->once())->method($extraDriverMethod);
        }
        $manager = new DrupalMailManager($driver);
        $manager->$method();
    }

    public static function dataProviderDriverDelegation(): \Iterator
    {
        yield 'startCollectingMail calls driver and clears' => ['startCollectingMail', 'startCollectingMail', ['clearMail']];
        yield 'stopCollectingMail delegates to driver' => ['stopCollectingMail', 'stopCollectingMail'];
        yield 'disableMail starts collecting' => ['disableMail', 'startCollectingMail', ['clearMail']];
        yield 'enableMail stops collecting' => ['enableMail', 'stopCollectingMail'];
        yield 'clearMail delegates to driver' => ['clearMail', 'clearMail'];
    }

    public function testGetMailDelegatesToDriver(): void
    {
        $expected = [['to' => 'a@b.com', 'subject' => 'test', 'body' => 'hello']];
        $driver = $this->createMock(DriverInterface::class);
        $driver->expects($this->once())->method('getMail')->willReturn($expected);
        $manager = new DrupalMailManager($driver);
        $this->assertSame($expected, $manager->getMail());
    }
}
