<?php

declare(strict_types=1);

namespace Drupal\DrupalExtension\Context;

use Behat\Behat\Context\Context;
use Behat\Testwork\Hook\HookDispatcher;

use Drupal\DrupalExtension\Manager\AuthenticationManagerInterface;
use Drupal\DrupalExtension\Manager\DriverManagerInterface;
use Drupal\DrupalExtension\Manager\UserManagerInterface;
use Drupal\DrupalExtension\ParametersAwareInterface;

/**
 * Interface for contexts that are aware of the Drupal driver manager.
 */
interface DrupalAwareInterface extends Context, ParametersAwareInterface {

  /**
   * Sets Drupal instance.
   */
  public function setDrupal(DriverManagerInterface $drupal): void;

  /**
   * Set event dispatcher.
   */
  public function setDispatcher(HookDispatcher $dispatcher): void;

  /**
   * Gets Drupal instance.
   *
   * @return \Drupal\DrupalExtension\Manager\DriverManagerInterface
   *   The Drupal driver manager instance.
   */
  public function getDrupal();

  /**
   * Sets the Drupal user manager instance.
   */
  public function setUserManager(UserManagerInterface $userManager): void;

  /**
   * Gets the Drupal user manager instance.
   *
   * @return \Drupal\DrupalExtension\Manager\UserManagerInterface
   *   The Drupal user manager instance.
   */
  public function getUserManager();

  /**
   * Sets the Drupal authentication manager instance.
   */
  public function setAuthenticationManager(AuthenticationManagerInterface $authenticationManager): void;

  /**
   * Gets the Drupal authentication manager instance.
   *
   * @return \Drupal\DrupalExtension\Manager\AuthenticationManagerInterface
   *   The Drupal authentication manager instance.
   */
  public function getAuthenticationManager();

}
