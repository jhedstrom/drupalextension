<?php

namespace Drupal\Exception;

use Drupal\Driver\DriverInterface;

/*
 * This file is part of the Behat\Mink.
 * (c) Konstantin Kudryashov <ever.zet@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Mink "element not found" exception.
 *
 * @author Konstantin Kudryashov <ever.zet@gmail.com>
 */
class UnsupportedDriverActionException extends Exception {
  /**
   * Initializes exception.
   *
   * @param string $template
   *   What is unsupported?
   * @param DriverInterface $driver
   *   Driver instance.
   * @param \Exception $previous
   *   Previous exception.
   */
  public function __construct($template, DriverInterface $driver, \Exception $previous = null) {
    $message = sprintf($template, get_class($driver));

    parent::__construct($message, $driver, $previous);
  }
}
