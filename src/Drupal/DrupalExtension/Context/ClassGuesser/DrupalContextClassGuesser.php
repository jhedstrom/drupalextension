<?php

namespace Drupal\DrupalExtension\Context\ClassGuesser;

use Behat\Behat\Context\ClassGuesser\ClassGuesserInterface;

/**
 * Drupal context class guesser.
 * Provides Drupal context class if no other class found.
 */
class DrupalContextClassGuesser implements ClassGuesserInterface {
  /**
   * Tries to guess context classname.
   *
   * @return string
   */
  public function guess() {
    return 'Drupal\\DrupalExtension\\Context\\DrupalContext';
  }
}
