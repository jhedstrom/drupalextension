<?php

declare(strict_types=1);

/**
 * @file
 * Contains \Drupal\DrupalExtension\Hook\Scope\AfterLanguageCreateScope.
 */
namespace Drupal\DrupalExtension\Hook\Scope;

/**
 * Represents a language hook scope.
 */
final class AfterLanguageCreateScope extends LanguageScope {

  /**
   * Return the scope name.
   *
   * @return string
   *   The hook scope name.
   */
  public function getName() {
    return self::AFTER;
  }

}
