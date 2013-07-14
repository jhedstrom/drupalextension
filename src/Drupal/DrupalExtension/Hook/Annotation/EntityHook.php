<?php

namespace Drupal\DrupalExtension\Hook\Annotation;

use Behat\Behat\Hook\Annotation\FilterableHook,
    Behat\Behat\Event\EventInterface,
    Behat\Gherkin\Filter\TagFilter;

/**
 * Entity hook class.
 */
abstract class EntityHook extends FilterableHook {

  /**
   * {@inheritdoc}
   */
  public function filterMatches(EventInterface $event) {
    if (NULL === ($filterString = $this->getFilter())) {
      return TRUE;
    }
  }

}
