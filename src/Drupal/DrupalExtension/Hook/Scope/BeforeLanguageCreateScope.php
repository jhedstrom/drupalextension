<?php

declare(strict_types=1);

/**
 * @file
 * Contains \Drupal\DrupalExtension\Hook\Scope\BeforeLanguageCreateScope.
 */
namespace Drupal\DrupalExtension\Hook\Scope;

/**
 * Represents a language hook scope.
 */
final class BeforeLanguageCreateScope extends LanguageScope {

  /**
   * Returns the scope name.
   *
   * @return string
   *   The hook scope name.
   */
  public function getName() {
    return self::BEFORE;
  }

}
