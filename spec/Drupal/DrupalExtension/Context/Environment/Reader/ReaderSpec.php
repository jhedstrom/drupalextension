<?php

namespace spec\Drupal\DrupalExtension\Context\Environment\Reader;

use PhpSpec\ObjectBehavior;

/**
 * Tests the Reader class.
 */
class ReaderSpec extends ObjectBehavior {

  public function it_is_initializable() {
    $this->shouldHaveType('Drupal\DrupalExtension\Context\Environment\Reader\Reader');
  }

}
