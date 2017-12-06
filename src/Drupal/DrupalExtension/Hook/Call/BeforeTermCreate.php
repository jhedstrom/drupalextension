<?php

namespace Drupal\DrupalExtension\Hook\Call;

use Drupal\DrupalExtension\Hook\Scope\TermScope;

/**
 * BeforeTermCreate hook class.
 */
class BeforeTermCreate extends EntityHook
{

  /**
   * Initializes hook.
   */
    public function __construct($filterString, $callable, $description = null)
    {
        parent::__construct(TermScope::BEFORE, $filterString, $callable, $description);
    }

  /**
   * {@inheritdoc}
   */
    public function getName()
    {
        return 'BeforeTermCreate';
    }
}
