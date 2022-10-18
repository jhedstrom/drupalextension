<?php
/**
 * @file
 * Entity scope.
 */
namespace Drupal\DrupalExtension\Hook\Scope;

use Behat\Testwork\Hook\Scope\HookScope;

/**
 * Represents an Entity hook scope.
 */
final class BeforeEntityCreateScope extends OtherEntityScope {

  /**
   * Return the scope name.
   *
   * @return string
   */
  public function getName() {
    return self::BEFORE;
  }

}
