<?php

declare(strict_types=1);

namespace Drupal\DrupalExtension\Hook\Call;

use Drupal\DrupalExtension\Hook\Scope\UserScope;

/**
 * AfterUserCreate hook class.
 */
class AfterUserCreate extends EntityHook {

  /**
   * Initializes hook.
   */
  public function __construct(string|null $filterString, array|callable $callable, string|null $description = NULL) {
    parent::__construct(UserScope::AFTER, $filterString, $callable, $description);
  }

  /**
   * {@inheritdoc}
   */
  public function getName() {
    return 'AfterUserCreate';
  }

}
