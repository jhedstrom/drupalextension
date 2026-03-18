<?php

declare(strict_types=1);

namespace Drupal\DrupalExtension\Hook\Call;

use Drupal\DrupalExtension\Hook\Scope\TermScope;

/**
 * AfterTermCreate hook class.
 */
class AfterTermCreate extends EntityHook {

  /**
   * Initializes hook.
   */
  public function __construct(string|null $filterString, array|callable $callable, string|null $description = NULL) {
    parent::__construct(TermScope::AFTER, $filterString, $callable, $description);
  }

  /**
   * {@inheritdoc}
   */
  public function getName() {
    return 'AfterTermCreate';
  }

}
