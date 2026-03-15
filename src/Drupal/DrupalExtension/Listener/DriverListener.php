<?php

declare(strict_types=1);

namespace Drupal\DrupalExtension\Listener;

use Behat\Behat\EventDispatcher\Event\ExampleTested;
use Behat\Behat\EventDispatcher\Event\ScenarioLikeTested;
use Behat\Behat\EventDispatcher\Event\ScenarioTested;

use Behat\Testwork\EventDispatcher\Event\LifecycleEvent;
use Drupal\DrupalDriverManager;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Drupal driver listener.
 *
 * Determines which Drupal driver to use for a given scenario or outline.
 */
class DriverListener implements EventSubscriberInterface {

  public function __construct(
    /**
     * Drupal driver manager.
     */
    private readonly DrupalDriverManager $drupalDriverManager,
    /**
     * Test parameters.
     */
    private array $parameters,
  ) {
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      ScenarioTested::BEFORE => ['prepareDefaultDrupalDriver', 11],
      ExampleTested::BEFORE => ['prepareDefaultDrupalDriver', 11],
    ];
  }

  /**
   * Configures default Drupal driver to use before each scenario or outline.
   *
   * `@api` tagged scenarios will get the `api_driver` as the default driver.
   *
   * Other scenarios get the `default_driver` as the default driver.
   *
   * @param \Behat\Behat\EventDispatcher\Event\ScenarioTested|\Behat\Behat\EventDispatcher\Event\OutlineTested $event
   *   The lifecycle event.
   */
  public function prepareDefaultDrupalDriver(LifecycleEvent $event): void {
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
    $this->drupalDriverManager->setDefaultDriverName($driver);

    // Set the environment.
    $environment = $event->getEnvironment();
    $this->drupalDriverManager->setEnvironment($environment);
  }

}
