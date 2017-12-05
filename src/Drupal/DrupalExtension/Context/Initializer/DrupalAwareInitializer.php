<?php

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
    /**
     * @var DrupalDriverManager
     */
    private $drupal;

    /**
     * @var array
     */
    private $parameters;

    /**
     * @var HookDispatcher
     */
    private $dispatcher;

    /**
     * @var DrupalAuthenticationManagerInterface
     */
    private $authenticationManager;

    /**
     * @var DrupalUserManagerInterface
     */
    private $userManager;

    public function __construct(DrupalDriverManager $drupal, array $parameters, HookDispatcher $dispatcher, DrupalAuthenticationManagerInterface $authenticationManager, DrupalUserManagerInterface $userManager)
    {
        $this->drupal = $drupal;
        $this->parameters = $parameters;
        $this->dispatcher = $dispatcher;
        $this->authenticationManager = $authenticationManager;
        $this->userManager = $userManager;
    }

  /**
   * {@inheritdocs}
   */
    public function initializeContext(Context $context)
    {

        // All contexts are passed here, only DrupalAwareInterface is allowed.
        if (!$context instanceof DrupalAwareInterface) {
            return;
        }

        // Set Drupal driver manager.
        $context->setDrupal($this->drupal);

        // Set event dispatcher.
        $context->setDispatcher($this->dispatcher);

        // Add all parameters to the context.
        $context->setDrupalParameters($this->parameters);

        // Set the Drupal authentication manager.
        $context->setAuthenticationManager($this->authenticationManager);

        // Set the Drupal user manager.
        $context->setUserManager($this->userManager);
    }
}
