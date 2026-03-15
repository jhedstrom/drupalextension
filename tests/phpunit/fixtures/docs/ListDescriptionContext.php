<?php

declare(strict_types=1);

namespace Drupal\DrupalExtension\Tests\Fixtures;

/**
 * Context with a list in description.
 *
 * Features:
 *
 * - First item
 * - Second item
 */
class ListDescriptionContext {

  /**
   * Step method.
   *
   * @Then the list should be visible
   *
   * @code
   * Then the list should be visible
   * @endcode
   */
  public function listAssertVisible(): void {
  }

}
