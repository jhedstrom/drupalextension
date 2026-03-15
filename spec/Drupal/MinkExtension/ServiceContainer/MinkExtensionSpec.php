<?php

namespace spec\Drupal\MinkExtension\ServiceContainer;

use Behat\Testwork\ServiceContainer\Extension;
use PhpSpec\ObjectBehavior;

/**
 * Tests the MinkExtension class.
 */
class MinkExtensionSpec extends ObjectBehavior {

  public function it_is_a_testwork_extension() {
    $this->shouldHaveType(Extension::class);
  }

  public function it_is_named_mink() {
    $this->getConfigKey()->shouldReturn('mink');
  }

}
