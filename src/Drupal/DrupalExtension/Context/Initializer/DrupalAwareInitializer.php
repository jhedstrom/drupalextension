<?php

namespace Drupal\DrupalExtension\Context\Initializer;

use Behat\Behat\Context\Initializer\InitializerInterface;
use Behat\Behat\Context\ContextInterface;

class DrupalAwareInitializer implements InitializerInterface {
  public function initialize(ContextInterface $context) {
  }

  public function supports(ContextInterface $context) {
    return $context instanceof DrupalAwareInterface;
  }
}
