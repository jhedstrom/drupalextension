<?php

declare(strict_types=1);

/**
 * @file
 * Entity scope.
 */
namespace Drupal\DrupalExtension\Hook\Scope;

use Behat\Behat\Context\Context;
use Behat\Testwork\Environment\Environment;
use Drupal\Driver\Entity\EntityStubInterface;

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
     * Entity stub.
     */
    private readonly EntityStubInterface $entityStub,
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
   * {@inheritdoc}
   */
  public function getStub(): EntityStubInterface {
    return $this->entityStub;
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
