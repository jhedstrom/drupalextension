<?php

namespace Drupal\DrupalExtension\Context;

use Behat\Behat\Context\Context;

use Drupal\Drupal;

interface DrupalSubContextInterface extends Context {
  /**
   * Instantiates the subcontext.
   *
   * @param \Drupal\Drupal
   *   The Drupal Driver manager.
   *
   * @return string
   */
  public function __construct(Drupal $context);
}
