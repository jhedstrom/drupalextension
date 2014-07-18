<?php

namespace Drupal\DrupalExtension\Context\Initializer;

use Behat\Behat\Context\Initializer\ContextInitializer;
use Behat\Behat\Context\Context;
use Behat\Testwork\Hook\HookDispatcher;

use Drupal\Drupal;
use Drupal\DrupalExtension\Context\DrupalContext;
use Drupal\DrupalExtension\Context\DrupalAwareInterface;
use Drupal\DrupalExtension\Context\DrupalSubContextFinderInterface;

use Symfony\Component\Finder\Finder;

class DrupalAwareInitializer implements ContextInitializer {
  private $drupal, $parameters, $dispatcher;

  public function __construct(Drupal $drupal, array $parameters, HookDispatcher $dispatcher) {
    $this->drupal = $drupal;
    $this->parameters = $parameters;
    $this->dispatcher = $dispatcher;
  }

  /**
   * {@inheritdocs}
   */
  public function initializeContext(Context $context) {

    // All contexts are passed here, only DrupalAwareInterface is allowed.
    if (!$context instanceof DrupalAwareInterface) {
      return;
    }

    // Store for reference during scenario/outline setup.
    $this->context = $context;

    // Set Drupal driver manager.
    $context->setDrupal($this->drupal);

    // Set event dispatcher.
    $context->setDispatcher($this->dispatcher);

    // Add all parameters to the context.
    $context->setDrupalParameters($this->parameters);

    // Add commonly used parameters as proper class variables.
    if (isset($this->parameters['basic_auth'])) {
      $context->basic_auth = $this->parameters['basic_auth'];
    }

    // Initialize any available sub-contexts.
    if (isset($this->parameters['subcontexts'])) {
      $paths = array();

      // Drivers may specify paths to subcontexts.
      if ($this->parameters['subcontexts']['autoload']) {
        foreach ($this->drupal->getDrivers() as $name => $driver) {
          if ($driver instanceof DrupalSubContextFinderInterface) {
            $paths += $driver->getSubContextPaths();
          }
        }
      }

      // Additional subcontext locations may be specified manually in behat.yml.
      if (isset($this->parameters['subcontexts']['paths'])) {
        $paths = array_merge($paths, $this->parameters['subcontexts']['paths']);
      }

      foreach ($paths as $path) {
        if ($subcontexts = $this->findAvailableSubContexts($path)) {
          $this->loadSubContexts($subcontexts);
        }
      }
      $context->initializeSubContexts();
    }
  }

  public function loadSubContexts($subcontexts) {
    foreach ($subcontexts as $path => $subcontext) {
      if (!file_exists($path)) {
        throw new \RuntimeException(sprintf('Subcontext path %s path does not exist.', $path));
      }

      // Load file.
      require_once $path;
    }
  }

  /**
   * Find Sub-contexts matching a given pattern located at the passed path.
   *
   * @param string $path
   *   Absolute path to the directory to search for sub-contexts.
   * @param string $pattern
   *   File pattern to match. Defaults to `*.behat.inc`.
   *
   * @return array
   *   An array of paths.
   */
  public function findAvailableSubContexts($path, $pattern = '*.behat.inc') {
    $paths = array();

    $finder = new Finder();
    $iterator = $finder
      ->files()
      ->name($pattern)
      ->in($path);

    foreach ($iterator as $found) {
      $paths[$found->getRealPath()] = $found->getFileName();
    }

    return $paths;
  }
}
