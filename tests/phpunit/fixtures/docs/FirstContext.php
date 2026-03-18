<?php

declare(strict_types=1);

namespace Drupal\DrupalExtension\Tests\Fixtures;

use Behat\Step\Then;

/**
 * First context for testing.
 */
class FirstContext {

  /**
   * First method.
   *
   *
   * @code
   * Then the first should pass
   * @endcode
   */
  #[Then('the first should pass')]
  public function firstAssertFirst(): void {
  }

}
