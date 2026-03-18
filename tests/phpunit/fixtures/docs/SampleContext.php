<?php

declare(strict_types=1);

namespace Drupal\DrupalExtension\Tests\Fixtures;

use Behat\Step\Then;

/**
 * Sample context for testing.
 */
class SampleContext {

  /**
   * Test method.
   *
   *
   * @code
   * Then the test should pass
   * @endcode
   */
  #[Then('the test should pass')]
  public function sampleAssertTest(): void {
  }

}
