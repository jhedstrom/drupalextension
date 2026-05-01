<?php

declare(strict_types=1);

namespace Drupal\DrupalExtension\Tests;

use Drupal\Driver\Capability\MailCapabilityInterface;
use Drupal\DrupalExtension\Manager\DrupalMailManager;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Tests the DrupalMailManager class.
 */
#[CoversClass(DrupalMailManager::class)]
class DrupalMailManagerTest extends TestCase {

  /**
   * Tests that manager methods delegate to the driver.
   *
   * @param string $method
   *   The manager method to invoke.
   * @param string $driver_method
   *   The driver method expected to be called.
   * @param array<int, string> $extra_driver_methods
   *   Additional driver methods that should be called.
   */
  #[DataProvider('dataProviderDriverDelegation')]
  public function testDriverDelegation(string $method, string $driver_method, array $extra_driver_methods = []): void {
    $driver = $this->createMock(MailCapabilityInterface::class);
    $driver->expects($this->once())->method($driver_method);

    foreach ($extra_driver_methods as $extra_driver_method) {
      $driver->expects($this->once())->method($extra_driver_method);
    }

    $manager = new DrupalMailManager($driver);
    $manager->$method();
  }

  /**
   * Provides data for testDriverDelegation().
   */
  public static function dataProviderDriverDelegation(): \Iterator {
    yield 'startCollectingMail calls driver and clears' => ['startCollectingMail', 'mailStartCollecting', ['mailClear']];
    yield 'stopCollectingMail delegates to driver' => ['stopCollectingMail', 'mailStopCollecting'];
    yield 'disableMail starts collecting' => ['disableMail', 'mailStartCollecting', ['mailClear']];
    yield 'enableMail stops collecting' => ['enableMail', 'mailStopCollecting'];
    yield 'clearMail delegates to driver' => ['clearMail', 'mailClear'];
  }

  /**
   * Tests that getMail() delegates to the driver.
   */
  public function testGetMailDelegatesToDriver(): void {
    $expected = [['to' => 'a@b.com', 'subject' => 'test', 'body' => 'hello']];
    $driver = $this->createMock(MailCapabilityInterface::class);
    $driver->expects($this->once())->method('mailGet')->willReturn($expected);
    $manager = new DrupalMailManager($driver);
    $this->assertSame($expected, $manager->getMail());
  }

}
