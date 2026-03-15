<?php

namespace spec\Drupal\DrupalExtension\ServiceContainer;

use Behat\Testwork\ServiceContainer\Extension;
use PhpSpec\ObjectBehavior;

/**
 * Tests the DrupalExtension class.
 */
class DrupalExtensionSpec extends ObjectBehavior {

  public function it_is_a_testwork_extension() {
    $this->shouldHaveType(Extension::class);
  }

  public function it_is_named_drupal() {
    $this->getConfigKey()->shouldReturn('drupal');
  }

}
