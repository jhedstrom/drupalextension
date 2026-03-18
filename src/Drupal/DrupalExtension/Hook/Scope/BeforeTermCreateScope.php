<?php

declare(strict_types=1);

/**
 * @file
 * Entity scope.
 */
namespace Drupal\DrupalExtension\Hook\Scope;

/**
 * Represents an Entity hook scope.
 */
final class BeforeTermCreateScope extends TermScope {

  /**
   * Return the scope name.
   *
   * @return string
   *   The hook scope name.
   */
  public function getName() {
    return self::BEFORE;
  }

}
