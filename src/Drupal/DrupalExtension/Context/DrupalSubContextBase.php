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
   */
  protected function getUser() {
    /** @var DrupalContext $context */
    $context = $this->getContext('\Drupal\DrupalExtension\Context\DrupalContext');
    if (empty($context->user)) {
      throw new \Exception('No user is logged in.');
    }

    return $context->user;
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
