<?php

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

  /**
   * The active Drupal driver.
   *
   * @var \Drupal\Driver\DriverInterface
   */
  protected $driver;

  /**
   * The name or config array of the initial mail backend.
   *
   * @var mixed
   */
  protected $initialMailBackend;
  
  public function __construct(DriverInterface $driver) {
    $this->driver = $driver;
  }

  /**
   * Replace the initial mail backend with the test mail backend.
   */
  protected function enableTestMailBackend() {
    if (is_null($this->initialMailBackend)) {
      $this->initialMailBackend = $this->driver->getMailBackend();
    }
    // @todo Use a collector that supports html after D#2223967 lands.
    $this->driver->setMailBackend('test_mail_collector');
  }

  /**
   * Restore the initial mail backend.
   */
  protected function restoreInitialMailBackend() {
    $this->driver->setMailBackend($this->initialMailBackend);
  }

  /**
   * {@inheritdoc}
   */
  public function startCollectingMail() {
    $this->enableTestMailBackend();
    $this->clearMail();
  }

  /**
   * {@inheritdoc}
   */
  public function stopCollectingMail() {
    $this->restoreInitialMailBackend();
  }
  
  /**
   * {@inheritdoc}
   */
  public function enableMail() {
    $this->restoreInitialMailBackend();
  }

  /**
   * {@inheritdoc}
   */
  public function disableMail() {
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
  public function clearMail($store = 'default') {
    $this->driver->clearMail();
  }

}
