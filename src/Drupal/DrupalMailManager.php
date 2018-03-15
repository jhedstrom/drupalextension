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
class DrupalMailManager implements DrupalMailManagerInterface
{

  /**
   * The active Drupal driver.
   *
   * @var \Drupal\Driver\DriverInterface
   */
    protected $driver;
  
    public function __construct(DriverInterface $driver)
    {
        $this->driver = $driver;
    }

  /**
   * {@inheritdoc}
   */
    public function startCollectingMail()
    {
        $this->driver->startCollectingMail();
        $this->clearMail();
    }

  /**
   * {@inheritdoc}
   */
    public function stopCollectingMail()
    {
        $this->driver->stopCollectingMail();
    }
  
  /**
   * {@inheritdoc}
   */
    public function enableMail()
    {
        $this->stopCollectingMail();
    }

  /**
   * {@inheritdoc}
   */
    public function disableMail()
    {
        $this->startCollectingMail();
    }
  
  /**
   * {@inheritdoc}
   */
    public function getMail($store = 'default')
    {
        return $this->driver->getMail();
    }

  /**
   * {@inheritdoc}
   */
    public function clearMail($store = 'default')
    {
        $this->driver->clearMail();
    }
}
