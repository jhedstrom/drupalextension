<?php
/**
 * @file
 * Entity scope.
 */
namespace Drupal\DrupalExtension\Hook\Scope;

use Behat\Testwork\Hook\Scope\HookScope;

/**
 * Represents an Entity hook scope.
 */
interface EntityScope extends HookScope
{

    const BEFORE = 'entity.create.before';
    const AFTER = 'entity.create.after';

  /**
   * Returns the context.
   *
   * @return \Behat\Behat\Context\Context
   */
    public function getContext();

  /**
   * Returns scope entity.
   *
   * @return StepNode
   */
    public function getEntity();
}
