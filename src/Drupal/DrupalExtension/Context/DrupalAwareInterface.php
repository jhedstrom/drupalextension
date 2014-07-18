<?php

namespace Drupal\DrupalExtension\Context;

use Drupal\Drupal;
use Symfony\Component\EventDispatcher\EventDispatcher;

interface DrupalAwareInterface {

  /**
   * Sets Drupal instance.
   */
  public function setDrupal(Drupal $drupal);

  /**
   * Set event dispatcher.
   */
  public function setDispatcher(EventDispatcher $dispatcher);

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
