<?php

namespace Drupal\DrupalExtension\Context;

use Behat\Behat\Context\Context;
use Behat\Testwork\Hook\HookDispatcher;

use Drupal\DrupalDriverManager;
use Drupal\DrupalUserManagerInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;

interface DrupalAwareInterface extends Context {

  /**
   * Sets Drupal instance.
   */
  public function setDrupal(DrupalDriverManager $drupal);

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

  /**
   * Sets the Drupal user manager instance.
   *
   * @param \Drupal\DrupalUserManagerInterface $userManager
   */
  public function setUserManager(DrupalUserManagerInterface $userManager);

  /**
   * Gets the Drupal user manager instance.
   *
   * @return \Drupal\DrupalUserManagerInterface
   */
  public function getUserManager();
}
