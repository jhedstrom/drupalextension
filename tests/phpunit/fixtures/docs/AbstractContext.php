<?php

declare(strict_types=1);

namespace Drupal\DrupalExtension\Tests\Fixtures;

use Behat\Step\Given;

/**
 * Abstract context for testing.
 */
abstract class AbstractContext {

  /**
   * Abstract step.
   *
   *
   * @code
   * Given I am abstract
   * @endcode
   */
  #[Given('I am abstract')]
  public function abstractStep(): void {
  }

}
