<?php

namespace spec\Drupal\DrupalExtension\Context;

use PhpSpec\ObjectBehavior;

/**
 * Tests the MessageContext class.
 */
class MessageContextSpec extends ObjectBehavior {

  public function it_is_drupal_aware() {
    $this->shouldHaveType('Drupal\DrupalExtension\Context\RawDrupalContext');
  }

  public function it_is_a_translatable_context() {
    $this->shouldHaveType('Behat\Behat\Context\TranslatableContext');
  }

}
