<?php

declare(strict_types=1);

namespace Drupal\DrupalExtension\Hook\Call;

use Drupal\DrupalExtension\Hook\Scope\EntityScope;

/**
 * BeforeEntityCreate hook class.
 */
class BeforeEntityCreate extends EntityHook {

  /**
   * Initializes hook.
   */
  public function __construct(string|null $filterString, array|callable $callable, string|null $description = NULL) {
    parent::__construct(EntityScope::BEFORE, $filterString, $callable, $description);
  }

  /**
   * {@inheritdoc}
   */
  public function getName() {
    return 'BeforeEntityCreate';
  }

}
