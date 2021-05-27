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
abstract class OtherEntityScope extends BaseEntityScope {

  const BEFORE = 'otherentity.create.before';
  const AFTER = 'otherentity.create.after';

}
