<?php

namespace Drupal\DrupalExtension\Context\Initializer;

use Behat\Behat\Context\Initializer\InitializerInterface,
    Behat\Behat\Context\ContextInterface,
    Behat\Behat\Event\ScenarioEvent,
    Behat\Behat\Event\OutlineEvent;

use Drupal\Drupal,
    Drupal\DrupalExtension\Context\DrupalContext,
    Drupal\DrupalExtension\Context\DrupalSubContextFinderInterface;

use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

use Symfony\Component\Finder\Finder;

class DrupalAwareInitializer implements InitializerInterface, EventSubscriberInterface {
  private $drupal, $parameters, $dispatcher;

  public function __construct(Drupal $drupal, array $parameters, EventDispatcher $dispatcher) {
    $this->drupal = $drupal;
    $this->parameters = $parameters;
    $this->dispatcher = $dispatcher;
  }

  public function initialize(ContextInterface $context) {
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
          $context->initializeSubContexts($subcontexts);
        }
      }
    }
  }

  public function supports(ContextInterface $context) {
    // @todo Create a DrupalAwareInterface instead, so developers don't have to
    // directly extend the DrupalContext class.
    return $context instanceof DrupalContext;
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
      'beforeScenario' => array('prepareDefaultDrupalDriver', 11),
      'beforeOutline' => array('prepareDefaultDrupalDriver', 11),
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
    $scenario = $event instanceof ScenarioEvent ? $event->getScenario() : $event->getOutline();

    // Set the default driver.
    $driver = $this->parameters['default_driver'];

    foreach ($scenario->getTags() as $tag) {
      if (isset($this->parameters[$tag . '_driver'])) {
        $driver = $this->parameters[$tag . '_driver'];
      }
    }

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
