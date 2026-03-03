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

class DrupalAwareInitializer implements ContextInitializer
{
    public function __construct(private readonly DrupalDriverManager $drupalDriverManager, private readonly array $parameters, private readonly HookDispatcher $hookDispatcher, private readonly DrupalAuthenticationManagerInterface $drupalAuthenticationManager, private readonly DrupalUserManagerInterface $drupalUserManager)
    {
    }

  /**
   * {@inheritdocs}
   */
    public function initializeContext(Context $context): void
    {

        // All contexts are passed here, only DrupalAwareInterface is allowed.
        if (!$context instanceof DrupalAwareInterface) {
            return;
        }

        // Set Drupal driver manager.
        $context->setDrupal($this->drupalDriverManager);

        // Set event dispatcher.
        $context->setDispatcher($this->hookDispatcher);

        // Add all parameters to the context.
        $context->setDrupalParameters($this->parameters);

        // Set the Drupal authentication manager.
        $context->setAuthenticationManager($this->drupalAuthenticationManager);

        // Set the Drupal user manager.
        $context->setUserManager($this->drupalUserManager);
    }
}
