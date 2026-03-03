<?php

declare(strict_types=1);

namespace Drupal\DrupalExtension\Selector;

use Behat\Mink\Selector\SelectorInterface;
use Behat\Mink\Selector\CssSelector;

/**
 * Custom "region" selector to help select Drupal regions
 */
class RegionSelector implements SelectorInterface
{
    public function __construct(private readonly CssSelector $cssSelector, private array $regionMap)
    {
    }

    /**
     * Translates provided locator into XPath.
     *
     * @param string $region
     * @return string
     * @throws \InvalidArgumentException
     */
    public function translateToXPath($region)
    {
        if (!isset($this->regionMap[$region])) {
            throw new \InvalidArgumentException(sprintf('The "%s" region isn\'t configured!', $region));
        }
        $css = $this->regionMap[$region];

        return $this->cssSelector->translateToXPath($css);
    }
}
