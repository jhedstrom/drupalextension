<?php

declare(strict_types=1);

/**
 * @file
 * Node scope.
 */
namespace Drupal\DrupalExtension\Hook\Scope;

/**
 * Represents an Entity hook scope.
 */
abstract class NodeScope extends BaseEntityScope {

  const BEFORE = 'node.create.before';
  const AFTER = 'node.create.after';

}
