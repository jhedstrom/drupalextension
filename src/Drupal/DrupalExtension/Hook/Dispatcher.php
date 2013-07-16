<?php

namespace Drupal\DrupalExtension\Hook;

use Behat\Behat\Event\EventInterface,
    Behat\Behat\Hook\HookInterface;

use Symfony\Component\EventDispatcher\EventDispatcher,
    Symfony\Component\EventDispatcher\EventSubscriberInterface;

use Drupal\DrupalExtension\Event\EntityEvent;

/**
 * Hook dispatcher.
 */
class Dispatcher implements EventSubscriberInterface {
  private $hooks  = array();

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events = array(
      'beforeNodeCreate', 'beforeTermCreate', 'beforeUserCreate'
    );

    return array_combine($events, $events);
  }

  /**
   * Adds hook into dispatcher.
   *
   * @param HookInterface $hook
   */
  public function addHook(HookInterface $hook) {
   if (!isset($this->hooks[$hook->getEventName()])) {
     $this->hooks[$hook->getEventName()] = array();
   }

   $this->hooks[$hook->getEventName()][] = $hook;
  }

  /**
   * Returns all available hooks.
   *
   * @return array
   */
  public function getHooks() {
    return $this->hooks;
  }

  /**
   * Cleans dispatcher.
   */
  public function clean() {
    $this->hooks = array();
  }

  /**
   * Listens to "beforeNodeCreate" event.
   *
   * @param EntityEvent $event
   *
   * @uses fireStepHooks()
   */
    public function beforeNodeCreate(EntityEvent $event) {
      $this->fireHooks(__FUNCTION__, $event);
    }

  /**
   * Listens to "beforeTermCreate" event.
   *
   * @param EntityEvent $event
   *
   * @uses fireStepHooks()
   */
    public function beforeTermCreate(EntityEvent $event) {
      $this->fireHooks(__FUNCTION__, $event);
    }

  /**
   * Listens to "beforeUserCreate" event.
   *
   * @param EntityEvent $event
   *
   * @uses fireStepHooks()
   */
    public function beforeUserCreate(EntityEvent $event) {
      $this->fireHooks(__FUNCTION__, $event);
    }

    /**
     * Runs hooks with specified name.
     *
     * @param string         $name  hooks name
     * @param EventInterface $event event to which hooks glued
     *
     * @throws \Exception
     */
    protected function fireHooks($name, EventInterface $event) {
      $hooks = isset($this->hooks[$name]) ? $this->hooks[$name] : array();

      foreach ($hooks as $hook) {
        $runable = $hook instanceof FilterableHook ? $hook->filterMatches($event) : true;

        if ($runable) {

          try {
            $hook->run($event);
          } catch (\Exception $e) {
            $this->addHookInformationToException($hook, $e);
            throw $e;
          }

        }
      }
    }

    /**
     * Adds hook information to exception thrown from it.
     *
     * @param HookInterface $hook      hook instance
     * @param \Exception    $exception exception
     */
    private function addHookInformationToException(HookInterface $hook, \Exception $exception)
    {
        $refl    = new \ReflectionObject($exception);
        $message = $refl->getProperty('message');

        $message->setAccessible(true);
        $message->setValue($exception, sprintf(
            'Exception has been thrown in "%s" hook, defined in %s'."\n\n%s",
            $hook->getEventName(),
            $hook->getPath(),
            $exception->getMessage()
        ));
    }
}
