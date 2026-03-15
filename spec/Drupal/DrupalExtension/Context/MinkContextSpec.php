<?php

namespace spec\Drupal\DrupalExtension\Context;

use PhpSpec\ObjectBehavior;

/**
 * Tests the MinkContext class.
 */
class MinkContextSpec extends ObjectBehavior {

  public function it_extends_the_mink_context() {
    $this->shouldHaveType('Behat\MinkExtension\Context\MinkContext');
  }

  public function it_is_a_translatable_context() {
    $this->shouldHaveType('Behat\Behat\Context\TranslatableContext');
  }

}
