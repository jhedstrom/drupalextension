<?php

declare(strict_types=1);

namespace Drupal\DrupalExtension\Context;

use Behat\Behat\Context\Context;
use Behat\Testwork\Hook\HookDispatcher;

use Drupal\DrupalDriverManagerInterface;
use Drupal\DrupalExtension\Manager\DrupalAuthenticationManagerInterface;
use Drupal\DrupalExtension\Manager\DrupalUserManagerInterface;

/**
 * Interface for contexts that are aware of the Drupal driver manager.
 */
interface DrupalAwareInterface extends Context {

  /**
   * Sets Drupal instance.
   */
  public function setDrupal(DrupalDriverManagerInterface $drupal): void;

  /**
   * Set event dispatcher.
   */
  public function setDispatcher(HookDispatcher $dispatcher): void;

  /**
   * Gets Drupal instance.
   *
   * @return \Drupal\DrupalDriverManagerInterface
   *   The Drupal driver manager instance.
   */
  public function getDrupal();

  /**
   * Sets parameters provided for Drupal.
   *
   * @param array<string, mixed> $parameters
   *   The Drupal parameters.
   */
  public function setDrupalParameters(array $parameters): void;

  /**
   * Sets the Drupal user manager instance.
   */
  public function setUserManager(DrupalUserManagerInterface $userManager): void;

  /**
   * Gets the Drupal user manager instance.
   *
   * @return \Drupal\DrupalExtension\Manager\DrupalUserManagerInterface
   *   The Drupal user manager instance.
   */
  public function getUserManager();

  /**
   * Sets the Drupal authentication manager instance.
   */
  public function setAuthenticationManager(DrupalAuthenticationManagerInterface $authenticationManager): void;

  /**
   * Gets the Drupal authentication manager instance.
   *
   * @return \Drupal\DrupalExtension\Manager\DrupalAuthenticationManagerInterface
   *   The Drupal authentication manager instance.
   */
  public function getAuthenticationManager();

}
