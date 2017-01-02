<?php

namespace Drupal\DrupalExtension\Hook\Call;

use Drupal\DrupalExtension\Hook\Scope\OtherEntityScope;

/**
 * AfterEntityCreate hook class.
 */
class AfterEntityCreate extends EntityHook {

  /**
   * Initializes hook.
   */
  public function __construct($filterString, $callable, $description = null) {
    parent::__construct(OtherEntityScope::AFTER, $filterString, $callable, $description);
  }

  /**
   * {@inheritdoc}
   */
  public function getName() {
    return 'AfterEntityCreate';
  }
}
