<?php

/**
 * Contains \FooFoo.
 */

use Behat\Behat\Tester\Exception\PendingException;
use Drupal\DrupalExtension\Context\DrupalSubContextBase;
use Drupal\DrupalExtension\Context\DrupalSubContextInterface;

/**
 * Example subcontext.
 */
class FooFoo extends DrupalSubContextBase implements DrupalSubContextInterface {

  /**
   * @Then /^I should have a subcontext definition$/
   */
  public function assertSubContextDefinition() {
    throw new PendingException();
  }

}
