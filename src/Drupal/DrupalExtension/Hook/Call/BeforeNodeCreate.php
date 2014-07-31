<?php

namespace Drupal\DrupalExtension\Hook\Call;

use Drupal\DrupalExtension\Hook\Scope\NodeScope;

/**
 * BeforeNodeCreate hook class.
 */
class BeforeNodeCreate extends EntityHook {

  /**
   * Initializes hook.
   */
  public function __construct($filterString, $callable, $description = null) {
    parent::__construct(NodeScope::BEFORE, $filterString, $callable, $description);
  }

  /**
   * {@inheritdoc}
   */
  public function getName() {
    return 'BeforeNodeCreate';
  }
}
