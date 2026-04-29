<?php

declare(strict_types=1);

namespace Drupal\DrupalExtension\Context;

use Behat\Mink\Element\NodeElement;
use Behat\Mink\Exception\ElementNotFoundException;
use Behat\Mink\Exception\ExpectationException;
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
   * @endcode
   */
  #[Then('I should see the button :button in the :region( region)')]
  public function regionButtonAssertExists(string $button, string $region): void {
    $this->assertRegionContainsButton($button, $region);
  }

  /**
   * Checks if a button (with the noun before "button") exists in a region.
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
   * Then I should see the "Submit" button in the "content"
   * Then I should see the "Submit" button in the "content" region
   * @endcode
   */
  #[Then('I should see the :button button in the :region( region)')]
  public function regionButtonAssertExistsByLabel(string $button, string $region): void {
    $this->assertRegionContainsButton($button, $region);
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
   * @endcode
   */
  #[Then('I should not see the button :button in the :region( region)')]
  public function regionButtonAssertNotExists(string $button, string $region): void {
    $this->assertRegionDoesNotContainButton($button, $region);
  }

  /**
   * Asserts a button (with noun before "button") does not exist in a region.
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
   * Then I should not see the "Delete" button in the "sidebar"
   * Then I should not see the "Delete" button in the "sidebar" region
   * @endcode
   */
  #[Then('I should not see the :button button in the :region( region)')]
  public function regionButtonAssertNotExistsByLabel(string $button, string $region): void {
    $this->assertRegionDoesNotContainButton($button, $region);
  }

  /**
   * Assert an element exists in a region.
   *
   * @code
   * Then I should see the "h2" element in the "content"
   * Then I should see the "h2" element in the "content" region
   * @endcode
   */
  #[Then('I should see the :tag element in the :region( region)')]
  public function regionElementAssertExists(string $tag, string $region): void {
    if (!$this->getRegion($region)->findAll('css', $tag)) {
      throw new ElementNotFoundException($this->getSession()->getDriver(), sprintf('element in the "%s" region', $region), 'css', $tag);
    }
  }

  /**
   * Assert an element does not exist in a region.
   *
   * @code
   * Then I should not see the "h2" element in the "sidebar"
   * Then I should not see the "h2" element in the "sidebar" region
   * @endcode
   */
  #[Then('I should not see the :tag element in the :region( region)')]
  public function regionElementAssertNotExists(string $tag, string $region): void {
    if ($this->getRegion($region)->findAll('css', $tag)) {
      throw new ExpectationException(sprintf('The element "%s" was found in the "%s" region on the page %s', $tag, $region, $this->getSession()->getCurrentUrl()), $this->getSession()->getDriver());
    }
  }

  /**
   * Assert text in an element within a region.
   *
   * @code
   * Then I should see "Welcome" in the "h2" element in the "content"
   * Then I should see "Welcome" in the "h2" element in the "content" region
   * @endcode
   */
  #[Then('I should see :text in the :tag element in the :region( region)')]
  public function regionElementTextAssertEquals(string $text, string $tag, string $region): void {
    $region_obj = $this->getRegion($region);

    foreach ($region_obj->findAll('css', $tag) as $result) {
      if ($result->getText() == $text) {
        return;
      }
    }

    throw new ExpectationException(sprintf('The text "%s" was not found in the "%s" element in the "%s" region on the page %s', $text, $tag, $region, $this->getSession()->getCurrentUrl()), $this->getSession()->getDriver());
  }

  /**
   * Assert text is not in an element within a region.
   *
   * @code
   * Then I should not see "Error" in the "div" element in the "content"
   * Then I should not see "Error" in the "div" element in the "content" region
   * @endcode
   */
  #[Then('I should not see :text in the :tag element in the :region( region)')]
  public function regionElementTextAssertNotEquals(string $text, string $tag, string $region): void {
    $region_obj = $this->getRegion($region);

    foreach ($region_obj->findAll('css', $tag) as $result) {
      if ($result->getText() == $text) {
        throw new ExpectationException(sprintf('The text "%s" was found in the "%s" element in the "%s" region on the page %s', $text, $tag, $region, $this->getSession()->getCurrentUrl()), $this->getSession()->getDriver());
      }
    }
  }

  /**
   * Assert an element with a specific attribute value exists in a region.
   *
   * @code
   * Then I should see the "a" element with the "href" attribute set to "/about" in the "footer"
   * Then I should see the "a" element with the "href" attribute set to "/about" in the "footer" region
   * @endcode
   */
  #[Then('I should see the :tag element with the :attribute attribute set to :value in the :region( region)')]
  public function regionElementAttributeAssertEquals(string $tag, string $attribute, string $value, string $region): void {
    $elements = $this->getRegion($region)->findAll('css', $tag);
    if (empty($elements)) {
      throw new ElementNotFoundException($this->getSession()->getDriver(), sprintf('element in the "%s" region', $region), 'css', $tag);
    }

    if (empty($attribute)) {
      return;
    }

    $attr_found = FALSE;

    foreach ($elements as $element) {
      $attr = $element->getAttribute($attribute);
      if (!empty($attr)) {
        $attr_found = TRUE;
        if (str_contains($attr, $value)) {
          return;
        }
      }
    }

    if (!$attr_found) {
      throw new ExpectationException(sprintf('The "%s" attribute is not present on the element "%s" in the "%s" region on the page %s', $attribute, $tag, $region, $this->getSession()->getCurrentUrl()), $this->getSession()->getDriver());
    }

    throw new ExpectationException(sprintf('The "%s" attribute does not equal "%s" on the element "%s" in the "%s" region on the page %s', $attribute, $value, $tag, $region, $this->getSession()->getCurrentUrl()), $this->getSession()->getDriver());
  }

  /**
   * Assert text in an element with a specific attribute value in a region.
   *
   * @code
   * Then I should see "About" in the "a" element with the "href" attribute set to "/about" in the "footer"
   * Then I should see "About" in the "a" element with the "href" attribute set to "/about" in the "footer" region
   * @endcode
   */
  #[Then('I should see :text in the :tag element with the :attribute attribute set to :value in the :region( region)')]
  public function regionElementTextAttributeAssertEquals(string $text, string $tag, string $attribute, string $value, string $region): void {
    $matched = $this->findElementByText($this->getRegion($region), $tag, $text, $region);

    if (!empty($attribute)) {
      $attr = $matched->getAttribute($attribute);
      if (empty($attr)) {
        throw new ExpectationException(sprintf('The "%s" attribute is not present on the element "%s" in the "%s" region on the page %s', $attribute, $tag, $region, $this->getSession()->getCurrentUrl()), $this->getSession()->getDriver());
      }

      if (!str_contains($attr, $value)) {
        throw new ExpectationException(sprintf('The "%s" attribute does not equal "%s" on the element "%s" in the "%s" region on the page %s', $attribute, $value, $tag, $region, $this->getSession()->getCurrentUrl()), $this->getSession()->getDriver());
      }
    }
  }

  /**
   * Assert text in an element with a specific CSS property value in a region.
   *
   * @code
   * Then I should see "Notice" in the "div" element with the "color" CSS property set to "red" in the "content"
   * Then I should see "Notice" in the "div" element with the "color" CSS property set to "red" in the "content" region
   * @endcode
   */
  #[Then('I should see :text in the :tag element with the :property CSS property set to :value in the :region( region)')]
  public function regionElementTextCssAssertEquals(string $text, string $tag, string $property, string $value, string $region): void {
    $matched = $this->findElementByText($this->getRegion($region), $tag, $text, $region);

    if (!empty($property)) {
      $style = $matched->getAttribute('style');

      $rules = explode(';', (string) $style);
      foreach ($rules as $rule) {
        if (str_contains($rule, $property)) {
          if (!str_contains($rule, $value)) {
            throw new ExpectationException(sprintf('The "%s" style property does not equal "%s" on the element "%s" in the "%s" region on the page %s', $property, $value, $tag, $region, $this->getSession()->getCurrentUrl()), $this->getSession()->getDriver());
          }
          return;
        }
      }

      throw new ExpectationException(sprintf('The "%s" style property was not found in the "%s" element in the "%s" region on the page %s', $property, $tag, $region, $this->getSession()->getCurrentUrl()), $this->getSession()->getDriver());
    }
  }

  /**
   * Assert that a button is present in a region.
   *
   * @param string $button
   *   The id|name|title|alt|value of the button.
   * @param string $region
   *   The region to inspect.
   *
   * @throws \Exception
   *   If region or button within it cannot be found.
   */
  protected function assertRegionContainsButton(string $button, string $region): void {
    if (!$this->getRegion($region)->findButton($button)) {
      throw new ElementNotFoundException($this->getSession()->getDriver(), sprintf('button in the "%s" region', $region), 'id|name|title|alt|value', $button);
    }
  }

  /**
   * Assert that a button is not present in a region.
   *
   * @param string $button
   *   The id|name|title|alt|value of the button.
   * @param string $region
   *   The region to inspect.
   *
   * @throws \Exception
   *   If region is not found or the button is found within the region.
   */
  protected function assertRegionDoesNotContainButton(string $button, string $region): void {
    if ($this->getRegion($region)->findButton($button)) {
      throw new ExpectationException(sprintf("The button '%s' was found in the region '%s' on the page %s but should not", $button, $region, $this->getSession()->getCurrentUrl()), $this->getSession()->getDriver());
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
      throw new ElementNotFoundException($this->getSession()->getDriver(), sprintf('element in the "%s" region', $region), 'css', $tag);
    }

    foreach ($elements as $element) {
      if ($element->getText() == $text) {
        return $element;
      }
    }

    throw new ExpectationException(sprintf('The text "%s" was not found in the "%s" element in the "%s" region on the page %s', $text, $tag, $region, $this->getSession()->getCurrentUrl()), $this->getSession()->getDriver());
  }

}
