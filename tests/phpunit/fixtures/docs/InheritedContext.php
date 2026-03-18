<?php

declare(strict_types=1);

namespace Drupal\DrupalExtension\Tests\Fixtures;

use Behat\Step\Then;

/**
 * Context with inherited methods for testing.
 */
class InheritedContext extends FirstContext {

  /**
   * Own method.
   *
   *
   * @code
   * Then the inherited should pass
   * @endcode
   */
  #[Then('the inherited should pass')]
  public function inheritedAssertOwn(): void {
  }

}
