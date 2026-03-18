<?php

declare(strict_types=1);

/**
 * @file
 * Override the output of proposed methods to match Drupal coding standards.
 */
namespace Drupal\DrupalExtension\Definition\Proposal;

use Behat\Behat\Definition\Proposal\AnnotatedDefinitionProposal as BaseAnnotatedDefinitionProposal;

/**
 * Generates definition proposals matching Drupal coding standards.
 */
class AnnotatedDefinitionProposal extends BaseAnnotatedDefinitionProposal {

  /**
   * Generates a code snippet for a step definition.
   *
   * @param string $regex
   *   The regular expression for the step.
   * @param string $methodName
   *   The method name for the step definition.
   * @param array $args
   *   The method arguments.
   *
   * @return string
   *   The generated PHP snippet.
   */
  protected function generateSnippet(string $regex, string $methodName, array $args) {
    return sprintf(<<<PHP
  /**
   * @%s /^%s$/
   */
  public function %s(%s) {
    throw new PendingException();
  }
PHP
        , '%s', $regex, $methodName, implode(', ', $args));
  }

}
