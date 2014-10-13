<?php

namespace Drupal\DrupalExtension\Context;

use Behat\Behat\Context\Context;

use Drupal\DrupalDriverManager;

interface DrupalSubContextInterface extends Context {
  /**
   * Instantiates the subcontext.
   *
   * @param \Drupal\Drupal
   *   The Drupal Driver manager.
   *
   * @return string
   */
  public function __construct(DrupalDriverManager $context);
}
