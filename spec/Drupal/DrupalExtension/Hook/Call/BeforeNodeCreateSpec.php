<?php

namespace spec\Drupal\DrupalExtension\Hook\Call;

use Drupal\DrupalExtension\Context\Annotation\Reader;
use Drupal\DrupalExtension\Hook\Call\BeforeNodeCreate;
use PhpSpec\ObjectBehavior;

/**
 * Tests the BeforeNodeCreate class.
 */
class BeforeNodeCreateSpec extends ObjectBehavior {

  public function it_accepts_a_callable() {
    $this->beConstructedWith(NULL, [Reader::class, 'readCallee'], NULL);
    $this->shouldHaveType(BeforeNodeCreate::class);
    $this->getName()->shouldReturn('BeforeNodeCreate');
  }

  public function it_accepts_an_array() {
    // Simulates the scenario where the context class is not yet
    // autoloadable and the callable array cannot be validated by PHP
    // as a true callable at construction time.
    $this->beConstructedWith(NULL, [Reader::class, 'readCallee'], NULL);
    $this->shouldHaveType(BeforeNodeCreate::class);
  }

  public function it_accepts_a_filter_string() {
    $this->beConstructedWith('article', [Reader::class, 'readCallee'], NULL);
    $this->getFilterString()->shouldReturn('article');
  }

}
