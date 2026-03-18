<?php

namespace spec\Drupal\DrupalExtension\Context;

use Behat\Behat\Context\TranslatableContext;
use Drupal\DrupalExtension\Context\RawDrupalContext;
use PhpSpec\ObjectBehavior;

/**
 * Tests the DrupalContext class.
 */
class DrupalContextSpec extends ObjectBehavior {

  public function it_is_drupal_aware() {
    $this->shouldHaveType(RawDrupalContext::class);
  }

  public function it_is_a_translatable_context() {
    $this->shouldHaveType(TranslatableContext::class);
  }

}
