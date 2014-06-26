<?php

namespace Drupal\DrupalExtension\Context;

use Behat\Behat\Context\Context;
use Behat\Testwork\Hook\HookDispatcher;

use Drupal\Drupal;
use Symfony\Component\EventDispatcher\EventDispatcher;

interface DrupalAwareInterface extends Context {

  /**
   * Sets Drupal instance.
   */
  public function setDrupal(Drupal $drupal);

  /**
   * Set event dispatcher.
   */
  public function setDispatcher(HookDispatcher $dispatcher);

  /**
   * Gets Drupal instance.
   */
  public function getDrupal();

  /**
   * Sets parameters provided for Drupal.
   *
   * @param array $parameters
   */
  public function setDrupalParameters(array $parameters);
}
