<?php

namespace Drupal\DrupalExtension\Hook\Call;

use Behat\Testwork\Hook\Call\RuntimeFilterableHook;
use Behat\Testwork\Hook\Scope\HookScope;

/**
 * Entity hook class.
 */
abstract class EntityHook extends RuntimeFilterableHook {

  /**
   * Initializes hook.
   */
  public function __construct($filterString, $callable, $description = null) {
    xdebug_break();
    parent::__construct('scope', $filterString, $callable, $description);
  }

  /**
   * {@inheritDoc}
   */
  public function filterMatches(HookScope $scope) {
    if (NULL === ($filterString = $this->getFilter())) {
      return TRUE;
    }
  }

  /**
   * Runs hook callback.
   *
   * @param EventInterface $event
   */
  public function run(EventInterface $event) {
    call_user_func($this->getCallbackForContext($event->getContext()), $event);
  }

}
