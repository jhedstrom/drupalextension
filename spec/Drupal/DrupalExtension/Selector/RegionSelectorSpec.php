<?php

namespace spec\Drupal\DrupalExtension\Selector;

use Behat\Mink\Selector\SelectorInterface;
use Behat\Mink\Selector\CssSelector;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class RegionSelectorSpec extends ObjectBehavior
{
    function let(CssSelector $selector)
    {
        $regionMap = array(
            'Left sidebar' => '#left-sidebar',
        );
        $this->beConstructedWith($selector, $regionMap);
    }

    function it_is_initializable()
    {
        $this->shouldHaveType('Drupal\DrupalExtension\Selector\RegionSelector');
    }

    function it_should_translate_to_xpath()
    {
        // @todo this is not returning properly for some reason.
        $xpath = $this->translateToXPath('Left sidebar');
    }

    function it_should_not_accept_invalid_regions()
    {
        $this->shouldThrow('\InvalidArgumentException')->duringTranslateToXPath('Invalid region');
    }
}
