<?php

namespace spec\Drupal\DrupalExtension\Selector;

use Behat\Mink\Selector\CssSelector;
use Drupal\DrupalExtension\Selector\RegionSelector;
use PhpSpec\ObjectBehavior;

/**
 * Tests the RegionSelector class.
 */
class RegionSelectorSpec extends ObjectBehavior {

  public function let(CssSelector $selector) {
    $region_map = [
      'Left sidebar' => '#left-sidebar',
    ];
    $selector->translateToXPath('#left-sidebar')->willReturn('some xpath');

    $this->beConstructedWith($selector, $region_map);
  }

  public function it_is_initializable() {
    $this->shouldHaveType(RegionSelector::class);
  }

  public function it_should_translate_to_xpath() {
    $this->translateToXPath('Left sidebar')->shouldBe('some xpath');
  }

  public function it_should_not_accept_invalid_regions() {
    $this->shouldThrow(\InvalidArgumentException::class)->duringTranslateToXPath('Invalid region');
  }

}
