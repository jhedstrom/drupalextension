<?php

namespace Drupal\DrupalExtension\Context\Initializer;

use Behat\Behat\Context\Initializer\ContextInitializer;
use Behat\Behat\Context\Context;
use Behat\Testwork\Hook\HookDispatcher;

use Drupal\DrupalDriverManager;
use Drupal\DrupalExtension\Context\DrupalContext;
use Drupal\DrupalExtension\Context\DrupalAwareInterface;
use Drupal\DrupalUserManagerInterface;

class DrupalAwareInitializer implements ContextInitializer {
  private $drupal, $parameters, $dispatcher, $userManager;

  public function __construct(DrupalDriverManager $drupal, array $parameters, HookDispatcher $dispatcher, DrupalUserManagerInterface $userManager) {
    $this->drupal = $drupal;
    $this->parameters = $parameters;
    $this->dispatcher = $dispatcher;
    $this->userManager = $userManager;
  }

  /**
   * {@inheritdocs}
   */
  public function initializeContext(Context $context) {

    // All contexts are passed here, only DrupalAwareInterface is allowed.
    if (!$context instanceof DrupalAwareInterface) {
      return;
    }

    // Set Drupal driver manager.
    $context->setDrupal($this->drupal);

    // Set event dispatcher.
    $context->setDispatcher($this->dispatcher);

    // Add all parameters to the context.
    $context->setDrupalParameters($this->parameters);

    // Set the Drupal user manager.
    $context->setUserManager($this->userManager);
  }

}
