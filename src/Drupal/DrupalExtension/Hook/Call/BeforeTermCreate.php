<?php

declare(strict_types=1);

namespace Drupal\DrupalExtension\Hook\Call;

use Drupal\DrupalExtension\Hook\Scope\TermScope;

/**
 * BeforeTermCreate hook class.
 */
class BeforeTermCreate extends EntityHook {

  /**
   * Initializes hook.
   */
  public function __construct(string|null $filterString, array|callable $callable, string|null $description = NULL) {
    parent::__construct(TermScope::BEFORE, $filterString, $callable, $description);
  }

  /**
   * {@inheritdoc}
   */
  public function getName() {
    return 'BeforeTermCreate';
  }

}
