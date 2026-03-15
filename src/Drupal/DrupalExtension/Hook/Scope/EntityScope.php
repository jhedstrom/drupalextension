<?php

declare(strict_types=1);

/**
 * @file
 * Entity scope.
 */
namespace Drupal\DrupalExtension\Hook\Scope;

use Behat\Testwork\Hook\Scope\HookScope;

/**
 * Represents an Entity hook scope.
 */
// phpcs:ignore Drupal.Classes.InterfaceName.InterfaceSuffix
interface EntityScope extends HookScope {

  const BEFORE = 'entity.create.before';
  const AFTER = 'entity.create.after';

  /**
   * Returns the context.
   *
   * @return \Behat\Behat\Context\Context
   *   The context object.
   */
  public function getContext();

  /**
   * Returns scope entity.
   *
   * @return \stdClass
   *   The entity object.
   */
  public function getEntity();

}
