<?php

namespace Drupal\DrupalExtension\Event;

use Behat\Behat\Context\Context;

use Symfony\Component\EventDispatcher\Event;

/**
 * Drupal entity event.
 */
class EntityEvent extends Event {

  private $context, $entity;

  /**
   * Initializes an entity event.
   */
  public function __construct(Context $context, $entity) {
    $this->context = $context;
    $this->entity = $entity;
  }

  /**
   * Returns the context object.
   */
  public function getContext() {
    return $this->context;
  }

  /**
   * Returns the entity object.
   */
  public function getEntity() {
    return $this->entity;
  }
}
