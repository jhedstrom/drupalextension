<?php

declare(strict_types=1);

namespace Drupal\DrupalExtension\Context\Initializer;

use Behat\Behat\Context\Initializer\ContextInitializer;
use Behat\Behat\Context\Context;
use Behat\Testwork\Hook\HookDispatcher;

use Drupal\DrupalDriverManager;
use Drupal\DrupalExtension\Context\DrupalAwareInterface;
use Drupal\DrupalExtension\Manager\DrupalAuthenticationManagerInterface;
use Drupal\DrupalExtension\Manager\DrupalUserManagerInterface;
use Drupal\DrupalExtension\ParametersAwareInterface;

/**
 * Initializes DrupalAwareInterface contexts with required dependencies.
 */
class DrupalAwareInitializer implements ContextInitializer {

  /**
   * Constructs a DrupalAwareInitializer object.
   *
   * @param \Drupal\DrupalDriverManager $drupalDriverManager
   *   The Drupal driver manager.
   * @param array<string, mixed> $parameters
   *   Configuration parameters.
   * @param \Behat\Testwork\Hook\HookDispatcher $hookDispatcher
   *   The hook dispatcher.
   * @param \Drupal\DrupalExtension\Manager\DrupalAuthenticationManagerInterface $drupalAuthenticationManager
   *   The Drupal authentication manager.
   * @param \Drupal\DrupalExtension\Manager\DrupalUserManagerInterface $drupalUserManager
   *   The Drupal user manager.
   */
  public function __construct(private readonly DrupalDriverManager $drupalDriverManager, private readonly array $parameters, private readonly HookDispatcher $hookDispatcher, private readonly DrupalAuthenticationManagerInterface $drupalAuthenticationManager, private readonly DrupalUserManagerInterface $drupalUserManager) {
  }

  /**
   * {@inheritdoc}
   */
  public function initializeContext(Context $context): void {

    // 'ParametersAwareInterface' is a strict subset of
    // 'DrupalAwareInterface' (the latter extends the former). Pass parameters
    // to any context that asks for them, then layer the heavier driver
    // wiring on top for full Drupal-aware contexts only.
    if ($context instanceof ParametersAwareInterface) {
      $context->setParameters($this->parameters);
    }

    if (!$context instanceof DrupalAwareInterface) {
      return;
    }

    $context->setDrupal($this->drupalDriverManager);
    $context->setDispatcher($this->hookDispatcher);
    $context->setAuthenticationManager($this->drupalAuthenticationManager);
    $context->setUserManager($this->drupalUserManager);
  }

}
