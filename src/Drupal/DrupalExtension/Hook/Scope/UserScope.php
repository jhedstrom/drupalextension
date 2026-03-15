<?php

declare(strict_types=1);

/**
 * @file
 * User scope.
 */
namespace Drupal\DrupalExtension\Hook\Scope;

/**
 * Represents an Entity hook scope.
 */
abstract class UserScope extends BaseEntityScope {

  const BEFORE = 'user.create.before';
  const AFTER = 'user.create.after';

}
