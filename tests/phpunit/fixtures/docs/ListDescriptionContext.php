<?php

declare(strict_types=1);

namespace Drupal\DrupalExtension\Tests\Fixtures;

use Behat\Step\Then;

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
   *
   * @code
   * Then the list should be visible
   * @endcode
   */
  #[Then('the list should be visible')]
  public function listAssertVisible(): void {
  }

}
