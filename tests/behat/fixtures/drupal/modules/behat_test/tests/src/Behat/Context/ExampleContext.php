<?php

declare(strict_types=1);

namespace Drupal\Tests\behat_test\Behat\Context;

use Behat\Step\When;
use Drupal\DrupalExtension\Context\RawDrupalContext;
use Drupal\Tests\behat_test\Behat\ExampleContextTrait;

/**
 * Demonstrates how a module ships its own Behat step definitions.
 *
 * Extends RawDrupalContext for the Drupal and Mink session helpers without
 * inheriting any prebuilt steps, then layers a module-specific step on top of
 * the shared trait helpers.
 */
class ExampleContext extends RawDrupalContext {

  use ExampleContextTrait;

  /**
   * Visit the custom login form the module exposes.
   *
   * @code
   * When I visit the module custom login page
   * @endcode
   */
  #[When('I visit the module custom login page')]
  public function visitModuleCustomLoginPage(): void {
    $this->visitPath($this->getCustomLoginPath());
  }

}
