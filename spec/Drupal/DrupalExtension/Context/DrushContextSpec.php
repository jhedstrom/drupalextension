<?php

namespace spec\Drupal\DrupalExtension\Context;

use Behat\Behat\Context\TranslatableContext;
use Drupal\DrupalExtension\Context\RawDrupalContext;
use PhpSpec\ObjectBehavior;

/**
 * Tests the DrushContext class.
 */
class DrushContextSpec extends ObjectBehavior {

  public function it_should_be_drupal_aware() {
    $this->shouldHaveType(RawDrupalContext::class);
  }

  public function it_will_catch_scenarios_without_any_output() {
    $this->shouldThrow(\RuntimeException::class)->duringReadDrushOutput();
  }

  public function it_is_a_translatable_context() {
    $this->shouldHaveType(TranslatableContext::class);
  }

}
