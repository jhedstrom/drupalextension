<?php

declare(strict_types=1);

/**
 * @file
 * Entity scope.
 */
namespace Drupal\DrupalExtension\Hook\Scope;

use Behat\Behat\Context\Context;
use Behat\Testwork\Environment\Environment;

/**
 * Represents an Entity hook scope.
 */
abstract class BaseEntityScope implements EntityScope {

  /**
   * Initializes the scope.
   */
  public function __construct(
    private readonly Environment $environment,
    /**
     * Context object.
     */
    private readonly Context $context,
    /**
     * Entity object.
     */
    private readonly \stdClass $entity,
  ) {
  }

  /**
   * Returns the context.
   *
   * @return \Behat\Behat\Context\Context
   *   The context object.
   */
  public function getContext() {
    return $this->context;
  }

  /**
   * Returns the entity object.
   */
  public function getEntity(): \stdClass {
    return $this->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function getEnvironment() {
    return $this->environment;
  }

  /**
   * {@inheritdoc}
   */
  public function getSuite() {
    return $this->environment->getSuite();
  }

}
