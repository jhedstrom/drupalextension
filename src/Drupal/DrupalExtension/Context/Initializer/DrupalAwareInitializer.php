<?php

namespace Drupal\DrupalExtension\Context\Initializer;

use Behat\Behat\Context\Initializer\InitializerInterface;
use Behat\Behat\Context\ContextInterface;

use Drupal\DrupalExtension\Context\DrupalContext;

class DrupalAwareInitializer implements InitializerInterface {
  public function __construct(array $parameters) {
    $this->parameters = $parameters;
  }

  public function initialize(ContextInterface $context) {
    // Add all parameters to the context.
    $context->parameters = $this->parameters;

    // Add commonly used parameters as proper class variables.
    $context->drushAlias = $this->parameters['drush_alias'];
    $context->basic_auth = $this->parameters['basic_auth'];
  }

  public function supports(ContextInterface $context) {
    // @todo Create a DrupalAwareInterface instead, so developers don't have to
    // directly extend the DrupalContext class.
    return $context instanceof DrupalContext;
  }
}
