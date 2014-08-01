<?php
/**
 * @file
 * User scope.
 */
namespace Drupal\DrupalExtension\Hook\Scope;

use Behat\Behat\Context\Context;
use Behat\Testwork\Hook\Scope\HookScope;

/**
 * Represents an Entity hook scope.
 */
abstract class UserScope extends BaseEntityScope {

  const BEFORE = 'user.create.before';
  const AFTER = 'user.create.after';

}
