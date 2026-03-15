<?php

declare(strict_types=1);

namespace Drupal\DrupalExtension\Tests\Fixtures;

/**
 * Context with inherited methods for testing.
 */
class InheritedContext extends FirstContext {

  /**
   * Own method.
   *
   * @Then the inherited should pass
   *
   * @code
   * Then the inherited should pass
   * @endcode
   */
  public function inheritedAssertOwn(): void {
  }

}
