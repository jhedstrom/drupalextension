<?php

/**
 * @file
 * Contains \Drupal\DrupalExtension\Context\DrupalSubContextBase.
 */

namespace Drupal\DrupalExtension\Context;

use Behat\Behat\Context\Environment\InitializedContextEnvironment;
use Drupal\DrupalDriverManager;

/**
 * Base class for subcontexts that use the Drupal API.
 */
abstract class DrupalSubContextBase extends RawDrupalContext implements DrupalSubContextInterface {

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
  public function __construct(DrupalDriverManager $drupal) {
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
  protected function getUser() {
    trigger_error('DrupalSubContextBase::getUser() is deprecated. Use RawDrupalContext::getUserManager()->getCurrentUser() instead.', E_USER_DEPRECATED);

    $user = $this->getUserManager()->getCurrentUser();

    if (empty($user)) {
      throw new \Exception('No user is logged in.');
    }

    return $user;
  }

  /**
   * Returns the Behat context that corresponds with the given class name.
   *
   * This is inspired by InitializedContextEnvironment::getContext() but also
   * returns subclasses of the given class name. This allows us to retrieve for
   * example DrupalContext even if it is overridden in a project.
   *
   * @param string $class
   *   A fully namespaced class name.
   *
   * @return \Behat\Behat\Context\Context|false
   *   The requested context, or FALSE if the context is not registered.
   */
  protected function getContext($class) {
    /** @var InitializedContextEnvironment $environment */
    $environment = $this->drupal->getEnvironment();
    foreach ($environment->getContexts() as $context) {
      if ($context instanceof $class) {
        return $context;
      }
    }

    return FALSE;
  }

}
