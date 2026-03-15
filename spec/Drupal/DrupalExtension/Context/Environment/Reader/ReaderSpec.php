<?php

namespace spec\Drupal\DrupalExtension\Context\Environment\Reader;

use PhpSpec\ObjectBehavior;

use Drupal\DrupalDriverManager;

/**
 * Tests the Reader class.
 */
class ReaderSpec extends ObjectBehavior {

  public function let(DrupalDriverManager $drupal) {
    $parameters = [];
    $this->beConstructedWith($drupal, $parameters);
  }

  public function it_is_initializable() {
    $this->shouldHaveType('Drupal\DrupalExtension\Context\Environment\Reader\Reader');
  }

}
