<?php

declare(strict_types=1);

namespace Drupal\DrupalExtension\Context;

use Behat\Mink\Element\NodeElement;
use Behat\Step\Then;
use Behat\MinkExtension\Context\RawMinkContext;
use Drupal\DrupalExtension\RegionTrait;

/**
 * Extensions to the Mink Extension.
 */
class MarkupContext extends RawMinkContext {

  use RegionTrait;

  /**
   * Checks if a button with id|name|title|alt|value exists in a region.
   *
   * @param string $button
   *   The id|name|title|alt|value of the button.
   * @param string $region
   *   The region in which the button should be found.
   *
   * @throws \Exception
   *   If region or button within it cannot be found.
   *
   * @code
   * Then I should see the button "Submit" in the "content"
   * Then I should see the button "Submit" in the "content" region
   * Then I should see the "Submit" button in the "content" region
   * @endcode
   */
  #[Then('I should see the button :button in the :region( region)')]
  #[Then('I should see the :button button in the :region( region)')]
  public function assertRegionButton(string $button, string $region): void {
    if (!$this->getRegion($region)->findButton($button)) {
      throw new \Exception(sprintf("The button '%s' was not found in the region '%s' on the page %s", $button, $region, $this->getSession()->getCurrentUrl()));
    }
  }

  /**
   * Asserts that a button does not exists in a region.
   *
   * @param string $button
   *   The id|name|title|alt|value of the button.
   * @param string $region
   *   The region in which the button should not be found.
   *
   * @throws \Exception
   *   If region is not found or the button is found within the region.
   *
   * @code
   * Then I should not see the button "Delete" in the "sidebar"
   * Then I should not see the button "Delete" in the "sidebar" region
   * Then I should not see the "Delete" button in the "sidebar" region
   * @endcode
   */
  #[Then('I should not see the button :button in the :region( region)')]
  #[Then('I should not see the :button button in the :region( region)')]
  public function assertNotRegionButton(string $button, string $region): void {
    if ($this->getRegion($region)->findButton($button)) {
      throw new \Exception(sprintf("The button '%s' was found in the region '%s' on the page %s but should not", $button, $region, $this->getSession()->getCurrentUrl()));
    }
  }

  /**
   * Assert an element exists in a region.
   *
   * @code
   * Then I see the "h2" element in the "content"
   * Then I should see the "h2" element in the "content" region
   * @endcode
   */
  #[Then('I( should) see the :tag element in the :region( region)')]
  public function assertRegionElement(string $tag, string $region): void {
    if (!$this->getRegion($region)->findAll('css', $tag)) {
      throw new \Exception(sprintf('The element "%s" was not found in the "%s" region on the page %s', $tag, $region, $this->getSession()->getCurrentUrl()));
    }
  }

  /**
   * Assert an element does not exist in a region.
   *
   * @code
   * Then I not see the "h2" element in the "sidebar"
   * Then I should not see the "h2" element in the "sidebar" region
   * @endcode
   */
  #[Then('I( should) not see the :tag element in the :region( region)')]
  public function assertNotRegionElement(string $tag, string $region): void {
    if ($this->getRegion($region)->findAll('css', $tag)) {
      throw new \Exception(sprintf('The element "%s" was found in the "%s" region on the page %s', $tag, $region, $this->getSession()->getCurrentUrl()));
    }
  }

  /**
   * Assert text in an element within a region.
   *
   * @code
   * Then I see "Welcome" in the "h2" element in the "content"
   * Then I should see "Welcome" in the "h2" element in the "content" region
   * @endcode
   */
  #[Then('I( should) see :text in the :tag element in the :region( region)')]
  public function assertRegionElementText(string $text, string $tag, string $region): void {
    $regionObj = $this->getRegion($region);

    foreach ($regionObj->findAll('css', $tag) as $result) {
      if ($result->getText() == $text) {
        return;
      }
    }

    throw new \Exception(sprintf('The text "%s" was not found in the "%s" element in the "%s" region on the page %s', $text, $tag, $region, $this->getSession()->getCurrentUrl()));
  }

  /**
   * Assert text is not in an element within a region.
   *
   * @code
   * Then I not see "Error" in the "div" element in the "content"
   * Then I should not see "Error" in the "div" element in the "content" region
   * @endcode
   */
  #[Then('I( should) not see :text in the :tag element in the :region( region)')]
  public function assertNotRegionElementText(string $text, string $tag, string $region): void {
    $regionObj = $this->getRegion($region);

    foreach ($regionObj->findAll('css', $tag) as $result) {
      if ($result->getText() == $text) {
        throw new \Exception(sprintf('The text "%s" was found in the "%s" element in the "%s" region on the page %s', $text, $tag, $region, $this->getSession()->getCurrentUrl()));
      }
    }
  }

  /**
   * Assert an element with a specific attribute value exists in a region.
   *
   * @code
   * Then I see the "a" element with the "href" attribute set to "/about" in the "footer"
   * Then I should see the "a" element with the "href" attribute set to "/about" in the "footer" region
   * @endcode
   */
  #[Then('I( should) see the :tag element with the :attribute attribute set to :value in the :region( region)')]
  public function assertRegionElementAttribute(string $tag, string $attribute, string $value, string $region): void {
    $elements = $this->getRegion($region)->findAll('css', $tag);
    if (empty($elements)) {
      throw new \Exception(sprintf('The element "%s" was not found in the "%s" region on the page %s', $tag, $region, $this->getSession()->getCurrentUrl()));
    }

    if (empty($attribute)) {
      return;
    }

    $attrFound = FALSE;

    foreach ($elements as $element) {
      $attr = $element->getAttribute($attribute);
      if (!empty($attr)) {
        $attrFound = TRUE;
        if (str_contains($attr, $value)) {
          return;
        }
      }
    }

    if (!$attrFound) {
      throw new \Exception(sprintf('The "%s" attribute is not present on the element "%s" in the "%s" region on the page %s', $attribute, $tag, $region, $this->getSession()->getCurrentUrl()));
    }

    throw new \Exception(sprintf('The "%s" attribute does not equal "%s" on the element "%s" in the "%s" region on the page %s', $attribute, $value, $tag, $region, $this->getSession()->getCurrentUrl()));
  }

  /**
   * Assert text in an element with a specific attribute value in a region.
   *
   * @code
   * Then I see "About" in the "a" element with the "href" attribute set to "/about" in the "footer"
   * Then I should see "About" in the "a" element with the "href" attribute set to "/about" in the "footer" region
   * @endcode
   */
  #[Then('I( should) see :text in the :tag element with the :attribute attribute set to :value in the :region( region)')]
  public function assertRegionElementTextAttribute(string $text, string $tag, string $attribute, string $value, string $region): void {
    $matched = $this->findElementByText($this->getRegion($region), $tag, $text, $region);

    if (!empty($attribute)) {
      $attr = $matched->getAttribute($attribute);
      if (empty($attr)) {
        throw new \Exception(sprintf('The "%s" attribute is not present on the element "%s" in the "%s" region on the page %s', $attribute, $tag, $region, $this->getSession()->getCurrentUrl()));
      }

      if (!str_contains($attr, $value)) {
        throw new \Exception(sprintf('The "%s" attribute does not equal "%s" on the element "%s" in the "%s" region on the page %s', $attribute, $value, $tag, $region, $this->getSession()->getCurrentUrl()));
      }
    }
  }

  /**
   * Assert text in an element with a specific CSS property value in a region.
   *
   * @code
   * Then I see "Notice" in the "div" element with the "color" CSS property set to "red" in the "content"
   * Then I should see "Notice" in the "div" element with the "color" CSS property set to "red" in the "content" region
   * @endcode
   */
  #[Then('I( should) see :text in the :tag element with the :property CSS property set to :value in the :region( region)')]
  public function assertRegionElementTextCss(string $text, string $tag, string $property, string $value, string $region): void {
    $matched = $this->findElementByText($this->getRegion($region), $tag, $text, $region);

    if (!empty($property)) {
      $style = $matched->getAttribute('style');

      $rules = explode(';', (string) $style);
      foreach ($rules as $rule) {
        if (str_contains($rule, $property)) {
          if (!str_contains($rule, $value)) {
            throw new \Exception(sprintf('The "%s" style property does not equal "%s" on the element "%s" in the "%s" region on the page %s', $property, $value, $tag, $region, $this->getSession()->getCurrentUrl()));
          }
          return;
        }
      }

      throw new \Exception(sprintf('The "%s" style property was not found in the "%s" element in the "%s" region on the page %s', $property, $tag, $region, $this->getSession()->getCurrentUrl()));
    }
  }

  /**
   * Finds an element matching a CSS tag whose text matches the given string.
   *
   * @param \Behat\Mink\Element\NodeElement $regionObj
   *   The region to search within.
   * @param string $tag
   *   The CSS selector for the element.
   * @param string $text
   *   The text to match.
   * @param string $region
   *   The name of the region (for error messages).
   *
   * @return \Behat\Mink\Element\NodeElement
   *   The matched element.
   *
   * @throws \Exception
   *   If no matching element is found.
   */
  protected function findElementByText(NodeElement $regionObj, string $tag, string $text, string $region): NodeElement {
    $elements = $regionObj->findAll('css', $tag);

    if (empty($elements)) {
      throw new \Exception(sprintf('The element "%s" was not found in the "%s" region on the page %s', $tag, $region, $this->getSession()->getCurrentUrl()));
    }

    foreach ($elements as $element) {
      if ($element->getText() == $text) {
        return $element;
      }
    }

    throw new \Exception(sprintf('The text "%s" was not found in the "%s" element in the "%s" region on the page %s', $text, $tag, $region, $this->getSession()->getCurrentUrl()));
  }

}
