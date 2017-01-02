<?php

namespace Drupal\DrupalExtension\Hook\Call;

use Drupal\DrupalExtension\Hook\Scope\OtherEntityScope;

/**
 * BeforeEntityCreate hook class.
 */
class BeforeEntityCreate extends EntityHook {

  /**
   * Initializes hook.
   */
  public function __construct($filterString, $callable, $description = null) {
    parent::__construct(OtherEntityScope::BEFORE, $filterString, $callable, $description);
  }

  /**
   * {@inheritdoc}
   */
  public function getName() {
    return 'BeforeEntityCreate';
  }

}
