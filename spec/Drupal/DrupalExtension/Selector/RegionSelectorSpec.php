<?php

namespace spec\Drupal\DrupalExtension\Selector;

use Behat\Mink\Selector\CssSelector;
use Drupal\DrupalExtension\Selector\RegionSelector;
use PhpSpec\ObjectBehavior;

class RegionSelectorSpec extends ObjectBehavior
{
    function let(CssSelector $selector)
    {
        $regionMap = array(
            'Left sidebar' => '#left-sidebar',
        );
        $selector->translateToXPath('#left-sidebar')->willReturn('some xpath');

        $this->beConstructedWith($selector, $regionMap);
    }

    function it_is_initializable()
    {
        $this->shouldHaveType(RegionSelector::class);
    }

    function it_should_translate_to_xpath()
    {
        $this->translateToXPath('Left sidebar')->shouldBe('some xpath');
    }

    function it_should_not_accept_invalid_regions()
    {
        $this->shouldThrow(\InvalidArgumentException::class)->duringTranslateToXPath('Invalid region');
    }
}
