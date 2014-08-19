<?php

namespace Drupal\DrupalExtension\Context\Initializer;

use Behat\Behat\Context\Initializer\ContextInitializer;
use Behat\Behat\Context\Context;
use Behat\Testwork\Hook\HookDispatcher;

use Drupal\Drupal;
use Drupal\DrupalExtension\Context\DrupalContext;
use Drupal\DrupalExtension\Context\DrupalAwareInterface;

class DrupalAwareInitializer implements ContextInitializer {
  private $drupal, $parameters, $dispatcher;

  public function __construct(Drupal $drupal, array $parameters, HookDispatcher $dispatcher) {
    $this->drupal = $drupal;
    $this->parameters = $parameters;
    $this->dispatcher = $dispatcher;
  }

  /**
   * {@inheritdocs}
   */
  public function initializeContext(Context $context) {

    // All contexts are passed here, only DrupalAwareInterface is allowed.
    if (!$context instanceof DrupalAwareInterface) {
      return;
    }

    // Store for reference during scenario/outline setup.
    $this->context = $context;

    // Set Drupal driver manager.
    $context->setDrupal($this->drupal);

    // Set event dispatcher.
    $context->setDispatcher($this->dispatcher);

    // Add all parameters to the context.
    $context->setDrupalParameters($this->parameters);

    // Add commonly used parameters as proper class variables.
    if (isset($this->parameters['basic_auth'])) {
      $context->basic_auth = $this->parameters['basic_auth'];
    }

  }

}
