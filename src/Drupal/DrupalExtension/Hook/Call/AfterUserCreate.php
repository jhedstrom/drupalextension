<?php

namespace Drupal\DrupalExtension\Hook\Call;

use Drupal\DrupalExtension\Hook\Scope\UserScope;

/**
 * AfterUserCreate hook class.
 */
class AfterUserCreate extends EntityHook {

  /**
   * Initializes hook.
   */
  public function __construct($filterString, $callable, $description = null) {
    parent::__construct(UserScope::AFTER, $filterString, $callable, $description);
  }

  /**
   * {@inheritdoc}
   */
  public function getName() {
    return 'AfterUserCreate';
  }
}
