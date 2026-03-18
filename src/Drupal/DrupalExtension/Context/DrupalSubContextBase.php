<?php

declare(strict_types=1);

namespace Drupal\DrupalExtension\Context;

use Drupal\DrupalDriverManager;

/**
 * Base class for subcontexts that use the Drupal API.
 */
abstract class DrupalSubContextBase extends RawDrupalContext implements DrupalSubContextInterface {

  /**
   * Constructs a DrupalSubContextBase object.
   *
   * @param \Drupal\DrupalDriverManager $drupal
   *   The Drupal driver manager.
   */
  public function __construct(protected DrupalDriverManager $drupal) {
  }

  /**
   * Get the currently logged in user from DrupalContext.
   *
   * @deprecated in drupal:4.0.0 and is removed from drupal:5.0.0. The currently logged in user is now available in all context classes. Use $this->getUserManager()->getCurrentUser() instead.
   *
   * @see \Drupal\DrupalExtension\Context\RawDrupalContext::getUserManager()
   */
  protected function getUser() {
    @trigger_error('DrupalSubContextBase::getUser() is deprecated. Use RawDrupalContext::getUserManager()->getCurrentUser() instead.', E_USER_DEPRECATED);

    $user = $this->getUserManager()->getCurrentUser();

    if (empty($user)) {
      throw new \Exception('No user is logged in.');
    }

    return $user;
  }

}
