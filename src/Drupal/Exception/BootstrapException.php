<?php

namespace Drupal\Exception;

/**
 * Bootstrap exception.
 */
class BootstrapException extends Exception {
  /**
   * Initializes exception.
   *
   * @param string $message
   * @param int $code
   * @param \Exception|null $previous
   */
  public function __construct($message, $code = 0, \Exception $previous = null) {
    parent::__construct($message, null, $code, $previous);
  }
}
