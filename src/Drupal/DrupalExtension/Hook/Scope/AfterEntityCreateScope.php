<?php
/**
 * @file
 * After entity create scope.
 */
namespace Drupal\DrupalExtension\Hook\Scope;

use Behat\Testwork\Hook\Scope\HookScope;

/**
 * Represents an Entity hook scope.
 */
final class AfterEntityCreateScope extends BaseEntityScope
{

  /**
   * Return the scope name.
   *
   * @return string
   */
    public function getName()
    {
        return self::AFTER;
    }
}
