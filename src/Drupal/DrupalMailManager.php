<?php

declare(strict_types=1);

namespace Drupal;

use Drupal\Driver\DriverInterface;

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
    protected DriverInterface $driver,
  ) {
  }

  /**
   * {@inheritdoc}
   */
  public function startCollectingMail(): void {
    $this->driver->startCollectingMail();
    $this->clearMail();
  }

  /**
   * {@inheritdoc}
   */
  public function stopCollectingMail(): void {
    $this->driver->stopCollectingMail();
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
  public function getMail($store = 'default') {
    return $this->driver->getMail();
  }

  /**
   * {@inheritdoc}
   */
  public function clearMail($store = 'default'): void {
    $this->driver->clearMail();
  }

}
