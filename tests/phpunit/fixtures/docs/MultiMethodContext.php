<?php

declare(strict_types=1);

namespace Drupal\DrupalExtension\Tests\Fixtures;

use Behat\Step\Then;
use Behat\Step\Given;
use Behat\Step\When;

/**
 * A context with multiple methods to test sorting.
 */
class MultiMethodContext {

  /**
   * Then step.
   *
   *
   * @code
   * Then the result should be visible
   * @endcode
   */
  #[Then('the result should be visible')]
  public function multimethodAssertResultVisible(): void {
  }

  /**
   * Given step.
   *
   *
   * @code
   * Given the following items:
   * @endcode
   */
  #[Given('the following items:')]
  public function multimethodGivenItems(): void {
  }

  /**
   * When step.
   *
   *
   * @code
   * When I click on "Submit"
   * @endcode
   */
  #[When('I click on :button')]
  public function multimethodClickButton(): void {
  }

}
