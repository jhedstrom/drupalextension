<?php

declare(strict_types=1);

namespace Drupal\DrupalExtension\Tests\Fixtures;

use Behat\Step\Given;
use Behat\Step\When;

/**
 * Context exercising the inverse 'Assert'/'should' validation checks.
 *
 * Each public step here intentionally violates one of the rules added in
 * 6.0: '@Given'/'@When' methods may not contain "Assert" in the method
 * name, and '@Given'/'@When' step text may not contain "should". Used by
 * the full extract_info → validate pipeline to confirm the validator
 * flags violations when they are encoded as real PHP attributes.
 */
class MisnamedAssertContext {

  /**
   * Method name contains 'Assert' on a Given step (action, not assertion).
   *
   * @code
   * Given I create the thing
   * @endcode
   */
  #[Given('I create the thing')]
  public function assertGivenAction(): void {
  }

  /**
   * Method name contains 'Assert' on a When step (action, not assertion).
   *
   * @code
   * When I trigger the thing
   * @endcode
   */
  #[When('I trigger the thing')]
  public function assertWhenAction(): void {
  }

  /**
   * Given step text contains 'should' (reserved for Then).
   *
   * @code
   * Given the page should be ready
   * @endcode
   */
  #[Given('the page should be ready')]
  public function pageIsReady(): void {
  }

  /**
   * When step text contains 'should' (reserved for Then).
   *
   * @code
   * When I think the request should succeed
   * @endcode
   */
  #[When('I think the request should succeed')]
  public function requestSucceeds(): void {
  }

}
