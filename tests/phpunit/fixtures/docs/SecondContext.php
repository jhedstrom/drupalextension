<?php

declare(strict_types=1);

namespace Drupal\DrupalExtension\Tests\Fixtures;

use Behat\Step\Then;

/**
 * Second context for testing.
 */
class SecondContext {

  /**
   * Second method.
   *
   *
   * @code
   * Then the second should pass
   * @endcode
   */
  #[Then('the second should pass')]
  public function secondAssertSecond(): void {
  }

}
