<?php

namespace Drupal\DrupalExtension\Context;

use Behat\Behat\Context\Context;
use Behat\Testwork\Hook\HookDispatcher;

use Drupal\DrupalDriverManagerInterface;
use Drupal\DrupalExtension\Manager\DrupalAuthenticationManagerInterface;
use Drupal\DrupalExtension\Manager\DrupalUserManagerInterface;

interface DrupalAwareInterface extends Context
{

  /**
   * Sets Drupal instance.
   */
    public function setDrupal(DrupalDriverManagerInterface $drupal);

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
   * @param \Drupal\DrupalExtension\Manager\DrupalUserManagerInterface $userManager
   */
    public function setUserManager(DrupalUserManagerInterface $userManager);

  /**
   * Gets the Drupal user manager instance.
   *
   * @return \Drupal\DrupalExtension\Manager\DrupalUserManagerInterface
   */
    public function getUserManager();

  /**
   * Sets the Drupal authentication manager instance.
   *
   * @param \Drupal\DrupalExtension\Manager\DrupalAuthenticationManagerInterface $authenticationManager
   */
    public function setAuthenticationManager(DrupalAuthenticationManagerInterface $authenticationManager);

  /**
   * Gets the Drupal authentication manager instance.
   *
   * @return \Drupal\DrupalExtension\Manager\DrupalAuthenticationManagerInterface
   */
    public function getAuthenticationManager();
}
