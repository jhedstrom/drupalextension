<?php
/**
 * @file
 * Before entity create scope.
 */
namespace Drupal\DrupalExtension\Hook\Scope;

use Behat\Testwork\Hook\Scope\HookScope;

/**
 * Represents an Entity hook scope.
 */
final class BeforeEntityCreateScope extends BaseEntityScope
{

  /**
   * Return the scope name.
   *
   * @return string
   */
    public function getName()
    {
        return self::BEFORE;
    }
}
