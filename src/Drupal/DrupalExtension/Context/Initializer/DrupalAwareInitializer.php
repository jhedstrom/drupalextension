<?php

namespace Drupal\DrupalExtension\Context\Initializer;

use Behat\Behat\Context\Initializer\InitializerInterface,
    Behat\Behat\Context\ContextInterface,
    Behat\Behat\Event\ScenarioEvent,
    Behat\Behat\Event\OutlineEvent;

use Drupal\Drupal,
    Drupal\DrupalExtension\Context\DrupalContext;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class DrupalAwareInitializer implements InitializerInterface, EventSubscriberInterface {
  private $drupal, $parameters;

  public function __construct(Drupal $drupal, array $parameters) {
    $this->drupal = $drupal;
    $this->parameters = $parameters;
  }

  public function initialize(ContextInterface $context) {
    // Set Drupal driver manager.
    $context->setDrupal($this->drupal);

    // Add all parameters to the context.
    $context->parameters = $this->parameters;

    // Add commonly used parameters as proper class variables.
    $context->basic_auth = $this->parameters['basic_auth'];
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
   * `@api1 tagged scenarios will get the `api_driver1 as the default driver.
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
      if ('api' === $tag) {
        $driver = $this->parameters['api_driver'];
      }
    }

    $this->drupal->setDefaultDriverName($driver);
  }
}
