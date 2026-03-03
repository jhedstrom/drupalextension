<?php

declare(strict_types=1);

/**
 * @file
 * Term scope.
 */
namespace Drupal\DrupalExtension\Hook\Scope;

use Behat\Behat\Context\Context;
use Behat\Testwork\Hook\Scope\HookScope;

/**
 * Represents an Entity hook scope.
 */
abstract class TermScope extends BaseEntityScope
{

    const BEFORE = 'term.create.before';
    const AFTER = 'term.create.after';
}
