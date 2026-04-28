<?php

declare(strict_types=1);

namespace Drupal;

use Drupal\Driver\Capability\MailCapabilityInterface;

/**
 * Default implementation of the Drupal mail manager service.
 *
 * This uses Drupal core's test_mail_collector mail backend, which both
 * collects outbound mail and prevents it from being sent. Therefore using
 * this implementation, mail is collected if and only if sending is disabled.
 */
class DrupalMailManager implements DrupalMailManagerInterface {

  public function __construct(
    /**
     * The active Drupal driver.
     */
    protected MailCapabilityInterface $driver,
  ) {
  }

  /**
   * {@inheritdoc}
   */
  public function startCollectingMail(): void {
    $this->driver->mailStartCollecting();
    $this->clearMail();
  }

  /**
   * {@inheritdoc}
   */
  public function stopCollectingMail(): void {
    $this->driver->mailStopCollecting();
  }

  /**
   * {@inheritdoc}
   */
  public function enableMail(): void {
    $this->stopCollectingMail();
  }

  /**
   * {@inheritdoc}
   */
  public function disableMail(): void {
    $this->startCollectingMail();
  }

  /**
   * {@inheritdoc}
   */
  public function getMail($store = 'default'): array {
    $this->assertDefaultStore($store);

    return $this->driver->mailGet();
  }

  /**
   * {@inheritdoc}
   */
  public function clearMail(string $store = 'default'): void {
    $this->assertDefaultStore($store);

    $this->driver->mailClear();
  }

  /**
   * Rejects non-default mail stores.
   *
   * The v3 driver's 'MailCapabilityInterface' exposes a single implicit
   * collector, so any '$store' other than 'default' is unsupported. Throw
   * loudly rather than silently misroute reads or clears that consumers
   * relied on under earlier multi-store implementations. The '$store'
   * parameter will be removed entirely in a follow-up.
   */
  protected function assertDefaultStore(string $store): void {
    if ($store !== 'default') {
      throw new \InvalidArgumentException(sprintf('Mail store "%s" is not supported - the active driver only exposes the default store.', $store));
    }
  }

}
