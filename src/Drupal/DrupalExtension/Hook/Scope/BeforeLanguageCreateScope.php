<?php

/**
 * @file
 * Contains \Drupal\DrupalExtension\Hook\Scope\BeforeLanguageCreateScope.
 */
namespace Drupal\DrupalExtension\Hook\Scope;

/**
 * Represents a language hook scope.
 */
final class BeforeLanguageCreateScope extends LanguageScope
{

  /**
   * Returns the scope name.
   *
   * @return string
   */
    public function getName()
    {
        return self::BEFORE;
    }
}
