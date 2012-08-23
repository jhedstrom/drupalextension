<?php
/**
 * @file
 * Override the output of proposed methods to match Drupal coding standards.
 */

namespace Drupal\DrupalExtension\Definition\Proposal;

use Behat\Behat\Definition\Proposal\AnnotatedDefinitionProposal as BaseAnnotatedDefinitionProposal;

class AnnotatedDefinitionProposal extends BaseAnnotatedDefinitionProposal {
  protected function generateSnippet($regex, $methodName, array $args) {
    return sprintf(<<<PHP
  /**
   * @%s /^%s$/
   */
  public function %s(%s) {
    throw new PendingException();
  }
PHP
      , '%s', $regex, $methodName, implode(', ', $args)
    );
  }
}
