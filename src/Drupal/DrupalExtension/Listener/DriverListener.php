<?php

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
class DriverListener implements EventSubscriberInterface
{

  /**
   * Drupal driver manager.
   *
   * @var \Drupal\DrupalDriverManager
   */
    private $drupal;

  /**
   * Test parameters.
   *
   * @var array
   */
    private $parameters;

    public function __construct(DrupalDriverManager $drupal, array $parameters)
    {
        $this->drupal = $drupal;
        $this->parameters = $parameters;
    }

  /**
   * {@inheritDoc}
   */
    public static function getSubscribedEvents()
    {
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
   * @param ScenarioTested|OutlineEvent $event
   */
    public function prepareDefaultDrupalDriver(LifecycleEvent $event)
    {
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

        // Set the environment.
        $environment = $event->getEnvironment();
        $this->drupal->setEnvironment($environment);
    }
}
