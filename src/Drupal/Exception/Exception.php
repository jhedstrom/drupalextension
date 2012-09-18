<?php

namespace Drupal\Exception;

use Drupal\Driver\DriverInterface;

/**
 * Drupal driver manager base exception class.
 */
abstract class Exception extends \Exception {
  private $driver;

  /**
   * Initializes Drupal driver manager exception.
   *
   * @param string $message
   * @param DriverInterface $driver
   * @param integer $code
   * @param \Exception $previous
   */
  public function __construct($message, DriverInterface $driver = null, $code = 0, \Exception $previous = null) {
    $this->driver = $driver;

    parent::__construct($message, $code, $previous);
  }

  /**
   * Returns exception session.
   *
   * @return Session
   */
  protected function getDriver() {
    return $this->driver;
  }
}
