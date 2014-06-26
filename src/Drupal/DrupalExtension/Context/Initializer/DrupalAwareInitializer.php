<?php

namespace Drupal\DrupalExtension\Context\Initializer;

use Behat\Behat\Context\Initializer\ContextInitializer;
use Behat\Behat\Context\Context;
use Behat\Behat\EventDispatcher\Event\OutlineTested;
use Behat\Behat\EventDispatcher\Event\ScenarioLikeTested;
use Behat\Behat\EventDispatcher\Event\ScenarioTested;
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
   * Returns an array of event names this subscriber wants to listen to.
   *
   * The array keys are event names and the value can be:
   *
   *  * The method name to call (priority defaults to 0)
   *  * An array composed of the method name to call and the priority
   *  * An array of arrays composed of the method names to call and respective
   *    priorities, or 0 if unset
   *
   * For instance:
   *
   *  * array('eventName' => 'methodName')
   *  * array('eventName' => array('methodName', $priority))
   *  * array('eventName' => array(array('methodName1', $priority), array('methodName2'))
   *
   * @return array The event names to listen to
   */
  public static function getSubscribedEvents() {
    return array(
      ScenarioTested::BEFORE => array('prepareDefaultDrupalDriver', 11),
      OutlineTested::BEFORE => array('prepareDefaultDrupalDriver', 11),
    );
  }

  /**
   * Configures default Drupal driver to use before each scenario or outline.
   *
   * `@api` tagged scenarios will get the `api_driver` as the default driver.
   *
   * Other scenarios get the `default_driver` as the default driver.
   *
   * @param ScenarioEvent|OutlineEvent $event
   */
  public function prepareDefaultDrupalDriver($event) {
    $feature = $event->getFeature();
    $scenario = $event instanceof ScenarioLikeTested ? $event->getScenario() : $event->getOutline();

    // Get the default driver.
    $driver = $this->parameters['default_driver'];

    foreach (array_merge($feature->getTags(), $scenario->getTags()) as $tag) {
      if (!empty($this->parameters[$tag . '_driver'])) {
        $driver = $this->parameters[$tag . '_driver'];
      }
    }

    // Set the default driver.
    $this->drupal->setDefaultDriverName($driver);
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
