<?php

/**
 * @file
 * Contains \Drupal\DrupalExtension\Context\DrupalSubContextBase.
 */

namespace Drupal\DrupalExtension\Context;

use Drupal\DrupalDriverManager;

/**
 * Base class for subcontexts that use the Drupal API.
 */
abstract class DrupalSubContextBase extends RawDrupalContext implements DrupalSubContextInterface
{

  /**
   * The Drupal Driver Manager.
   *
   * @var \Drupal\DrupalDriverManager $drupal
   */
    protected $drupal;

  /**
   * Constructs a DrupalSubContextBase object.
   *
   * @param \Drupal\DrupalDriverManager $drupal
   *   The Drupal driver manager.
   */
    public function __construct(DrupalDriverManager $drupal)
    {
        $this->drupal = $drupal;
    }

  /**
   * Get the currently logged in user from DrupalContext.
   *
   * @deprecated
   *   Deprecated in 4.x, will be removed before 5.x.
   *   The currently logged in user is now available in all context classes.
   *   Use $this->getUserManager()->getCurrentUser() instead.
   */
    protected function getUser()
    {
        trigger_error('DrupalSubContextBase::getUser() is deprecated. Use RawDrupalContext::getUserManager()->getCurrentUser() instead.', E_USER_DEPRECATED);

        $user = $this->getUserManager()->getCurrentUser();

        if (empty($user)) {
            throw new \Exception('No user is logged in.');
        }

        return $user;
    }
}
