<?php

namespace spec\Drupal\DrupalExtension\Hook\Call;

use Drupal\DrupalExtension\Context\Attribute\HookAttributeReader;
use Drupal\DrupalExtension\Hook\Call\AfterTermCreate;
use PhpSpec\ObjectBehavior;

/**
 * Tests the AfterTermCreate class.
 */
class AfterTermCreateSpec extends ObjectBehavior {

  public function it_accepts_a_callable() {
    $this->beConstructedWith(NULL, [HookAttributeReader::class, 'readCallees'], NULL);
    $this->shouldHaveType(AfterTermCreate::class);
    $this->getName()->shouldReturn('AfterTermCreate');
  }

  public function it_accepts_an_array() {
    // Simulates the scenario where the context class is not yet
    // autoloadable and the callable array cannot be validated by PHP
    // as a true callable at construction time.
    $this->beConstructedWith(NULL, [HookAttributeReader::class, 'readCallees'], NULL);
    $this->shouldHaveType(AfterTermCreate::class);
  }

  public function it_accepts_a_filter_string() {
    $this->beConstructedWith('tags', [HookAttributeReader::class, 'readCallees'], NULL);
    $this->getFilterString()->shouldReturn('tags');
  }

}
