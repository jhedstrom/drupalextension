<?php

declare(strict_types=1);

namespace Drupal\DrupalExtension;

/**
 * Declares the ability to emit deprecation notices.
 */
interface DeprecationInterface {

  /**
   * Emits a deprecation notice for the given message.
   *
   * Implementations should ensure each unique message is emitted only once
   * per process so repeated calls during a long Behat run do not spam the
   * output.
   *
   * @param string $message
   *   The deprecation message to emit.
   */
  public function triggerDeprecation(string $message): void;

}
