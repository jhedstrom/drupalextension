<?php

declare(strict_types=1);

namespace Drupal\Tests\behat_test\Behat;

/**
 * Reusable helpers shared by the module's Behat contexts.
 *
 * Keeping helper methods in a trait lets the step definitions stay thin and
 * lets the same helpers back several contexts without duplication.
 */
trait ExampleContextTrait {

  /**
   * Return the path to the custom login form the module exposes.
   *
   * @return string
   *   The internal path, relative to the site root.
   */
  protected function getCustomLoginPath(): string {
    return '/custom-login';
  }

}
