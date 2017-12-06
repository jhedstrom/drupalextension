<?php
/**
 * @file
 * Node scope.
 */
namespace Drupal\DrupalExtension\Hook\Scope;

use Behat\Behat\Context\Context;
use Behat\Testwork\Hook\Scope\HookScope;

/**
 * Represents an Entity hook scope.
 */
abstract class NodeScope extends BaseEntityScope
{

    const BEFORE = 'node.create.before';
    const AFTER = 'node.create.after';
}
