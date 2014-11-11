<?php

namespace Drupal\DrupalExtension\Context;

use Behat\Mink\Exception\UnsupportedDriverActionException;
use Behat\MinkExtension\Context\RawMinkContext;

/**
 * Extensions to the Mink Extension.
 */
class MarkupContext extends RawMinkContext {

  /**
   * Return a region from the current page.
   *
   * @throws \Exception
   *   If region cannot be found.
   *
   * @param string $region
   *   The machine name of the region to return.
   *
   * @return \Behat\Mink\Element\NodeElement
   */
  public function getRegion($region) {
    $session = $this->getSession();
    $regionObj = $session->getPage()->find('region', $region);
    if (!$regionObj) {
      throw new \Exception(sprintf('No region "%s" found on the page %s.', $region, $session->getCurrentUrl()));
    }

    return $regionObj;
  }

  /**
   * @Then I should see the link :link
   */
  public function assertLinkVisible($link) {
    $element = $this->getSession()->getPage();
    $result = $element->findLink($link);

    try {
      if ($result && !$result->isVisible()) {
        throw new \Exception(sprintf("No link to '%s' on the page %s", $link, $this->getSession()->getCurrentUrl()));
      }
    }
    catch (UnsupportedDriverActionException $e) {
      // We catch the UnsupportedDriverActionException exception in case
      // this step is not being performed by a driver that supports javascript.
      // All other exceptions are valid.
    }

    if (empty($result)) {
      throw new \Exception(sprintf("No link to '%s' on the page %s", $link, $this->getSession()->getCurrentUrl()));
    }
  }

  /**
   * @Then I should not see the link :link
   */
  public function assertNotLinkVisible($link) {
    $element = $this->getSession()->getPage();
    $result = $element->findLink($link);

    try {
      if ($result && $result->isVisible()) {
        throw new \Exception(sprintf("The link '%s' was present on the page %s and was not supposed to be", $link, $this->getSession()->getCurrentUrl()));
      }
    }
    catch (UnsupportedDriverActionException $e) {
      // We catch the UnsupportedDriverActionException exception in case
      // this step is not being performed by a driver that supports javascript.
      // All other exceptions are valid.
    }

    if ($result) {
      throw new \Exception(sprintf("The link '%s' was present on the page %s and was not supposed to be", $link, $this->getSession()->getCurrentUrl()));
    }
  }

  /**
   * @Then I (should )see the heading :heading
   */
  public function assertHeading($heading) {
    $element = $this->getSession()->getPage();
    foreach (array('h1', 'h2', 'h3', 'h4', 'h5', 'h6') as $tag) {
      $results = $element->findAll('css', $tag);
      foreach ($results as $result) {
        if ($result->getText() == $heading) {
          return;
        }
      }
    }
    throw new \Exception(sprintf("The text '%s' was not found in any heading on the page %s", $heading, $this->getSession()->getCurrentUrl()));
  }

  /**
   * @Then I (should )not see the heading :heading
   */
  public function assertNotHeading($heading) {
    $element = $this->getSession()->getPage();
    foreach (array('h1', 'h2', 'h3', 'h4', 'h5', 'h6') as $tag) {
      $results = $element->findAll('css', $tag);
      foreach ($results as $result) {
        if ($result->getText() == $heading) {
          throw new \Exception(sprintf("The text '%s' was found in a heading on the page %s", $heading, $this->getSession()->getCurrentUrl()));
        }
      }
    }
  }

  /**
   * @Then I (should ) see the button :button
   * @Then I (should ) see the :button button
   */
  public function assertButton($button) {
    $element = $this->getSession()->getPage();
    $buttonObj = $element->findButton($button);
    if (empty($buttonObj)) {
      throw new \Exception(sprintf("The button '%s' was not found on the page %s", $button, $this->getSession()->getCurrentUrl()));
    }
  }

  /**
   * Checks if a button with id|name|title|alt|value exists in a region
   *
   * @Then I should see the button :button in the :region( region)
   * @Then I should see the :button button in the :region( region)
   *
   * @param $button
   *   string The id|name|title|alt|value of the button
   * @param $region
   *   string The region in which the button should be found
   *
   * @throws \Exception
   *   If region or button within it cannot be found.
   */
  public function assertRegionButton($button, $region) {
    $regionObj = $this->getRegion($region);

    $buttonObj = $regionObj->findButton($button);
    if (empty($buttonObj)) {
      throw new \Exception(sprintf("The button '%s' was not found in the region '%s' on the page %s", $button, $region, $this->getSession()->getCurrentUrl()));
    }
  }

  /**
   * Find a heading in a specific region.
   *
   * @Then I should see the heading :heading in the :region( region)
   * @Then I should see the :heading heading in the :region( region)
   *
   * @throws \Exception
   *   If region or header within it cannot be found.
   */
  public function assertRegionHeading($heading, $region) {
    $regionObj = $this->getRegion($region);

    foreach (array('h1', 'h2', 'h3', 'h4', 'h5', 'h6') as $tag) {
      $elements = $regionObj->findAll('css', $tag);
      if (!empty($elements)) {
        foreach ($elements as $element) {
          if (trim($element->getText()) === $heading) {
            return;
          }
        }
      }
    }

    throw new \Exception(sprintf('The heading "%s" was not found in the "%s" region on the page %s', $heading, $region, $this->getSession()->getCurrentUrl()));
  }

  /**
   * @Then I( should) see the :tag element in the :region( region)
   */
  public function assertRegionElement($tag, $region) {
    $regionObj = $this->getRegion($region);
    $elements = $regionObj->findAll('css', $tag);
    if (!empty($elements)) {
      return;
    }
    throw new \Exception(sprintf('The element "%s" was not found in the "%s" region on the page %s', $tag, $region, $this->getSession()->getCurrentUrl()));
  }

  /**
   * @Then I( should) not see the :tag element in the :region( region)
   */
  public function assertNotRegionElement($tag, $region) {
    $regionObj = $this->getRegion($region);
    $result = $regionObj->findAll('css', $tag);
    if (!empty($result)) {
      throw new \Exception(sprintf('The element "%s" was found in the "%s" region on the page %s', $tag, $region, $this->getSession()->getCurrentUrl()));
    }
  }

  /**
   * @Then I( should) not see :text in the :tag element in the :region( region)
   */
  public function assertNotRegionElementText($text, $tag, $region) {
    $regionObj = $this->getRegion($region);
    $results = $regionObj->findAll('css', $tag);
    if (!empty($results)) {
      foreach ($results as $result) {
        if ($result->getText() == $text) {
          throw new \Exception(sprintf('The text "%s" was found in the "%s" element in the "%s" region on the page %s', $text, $tag, $region, $this->getSession()->getCurrentUrl()));
        }
      }
    }
  }

  /**
   * @Then I should see the link :link in the :region( region)
   *
   * @throws \Exception
   *   If region or link within it cannot be found.
   */
  public function assertLinkRegion($link, $region) {
    $regionObj = $this->getRegion($region);

    $result = $regionObj->findLink($link);
    if (empty($result)) {
      throw new \Exception(sprintf('No link to "%s" in the "%s" region on the page %s', $link, $region, $this->getSession()->getCurrentUrl()));
    }
  }

  /**
   * @Then I should not see the link :link in the :region( region)
   *
   * @throws \Exception
   *   If region or link within it cannot be found.
   */
  public function assertNotLinkRegion($link, $region) {
    $regionObj = $this->getRegion($region);

    $result = $regionObj->findLink($link);
    if (!empty($result)) {
      throw new \Exception(sprintf('Link to "%s" in the "%s" region on the page %s', $link, $region, $this->getSession()->getCurrentUrl()));
    }
  }

  /**
   * @Then I should see( the text) :text in the :region( region)
   *
   * @throws \Exception
   *   If region or text within it cannot be found.
   */
  public function assertRegionText($text, $region) {
    $regionObj = $this->getRegion($region);

    // Find the text within the region
    $regionText = $regionObj->getText();
    if (strpos($regionText, $text) === FALSE) {
      throw new \Exception(sprintf("The text '%s' was not found in the region '%s' on the page %s", $text, $region, $this->getSession()->getCurrentUrl()));
    }
  }

  /**
   * @Then I should not see( the text) :text in the :region( region)
   *
   * @throws \Exception
   *   If region or text within it cannot be found.
   */
  public function assertNotRegionText($text, $region) {
    $regionObj = $this->getRegion($region);

    // Find the text within the region.
    $regionText = $regionObj->getText();
    if (strpos($regionText, $text) !== FALSE) {
      throw new \Exception(sprintf('The text "%s" was found in the region "%s" on the page %s', $text, $region, $this->getSession()->getCurrentUrl()));
    }
  }

  /**
   * @Then I( should) see the :tag element with the :attribute attribute set to :value in the :region( region)
   */
  public function assertRegionElementAttribute($tag, $attribute, $value, $region) {
    $regionObj = $this->getRegion($region);
    $elements = $regionObj->findAll('css', $tag);
    if (empty($elements)) {
      throw new \Exception(sprintf('The element "%s" was not found in the "%s" region on the page %s', $tag, $region, $this->getSession()->getCurrentUrl()));
    }
    if (!empty($attribute)) {
      $found = FALSE;
      foreach ($elements as $element) {
      $attr = $element->getAttribute($attribute);
        if (!empty($attr)) {
          $found = TRUE;
          break;
        }
        if (strpos($attr, "$value") === FALSE) {
          throw new \Exception(sprintf('The "%s" attribute does not equal "%s" on the element "%s" in the "%s" region on the page %s', $attribute, $value, $tag, $region, $this->getSession()->getCurrentUrl()));
        }
      }
      if (!$found) {
        throw new \Exception(sprintf('The "%s" attribute is not present on the element "%s" in the "%s" region on the page %s', $attribute, $tag, $region, $this->getSession()->getCurrentUrl()));
      }
    }
  }

  /**
   * @Then I( should) see :text in the :tag element with the :attribute attribute set to :value in the :region( region)
   */
  public function assertRegionElementTextAttribute($text, $tag, $attribute, $value, $region) {
    $regionObj = $this->getRegion($region);
    $elements = $regionObj->findAll('css', $tag);
    if (empty($elements)) {
      throw new \Exception(sprintf('The element "%s" was not found in the "%s" region on the page %s', $tag, $region, $this->getSession()->getCurrentUrl()));
    }

    $found = FALSE;
    foreach ($elements as $element) {
      if ($element->getText() == $text) {
        $found = TRUE;
        break;
      }
    }
    if (!$found) {
      throw new \Exception(sprintf('The text "%s" was not found in the "%s" element in the "%s" region on the page %s', $text, $tag, $region, $this->getSession()->getCurrentUrl()));
    }

    if (!empty($attribute)) {
      $attr = $element->getAttribute($attribute);
      if (empty($attr)) {
        throw new \Exception(sprintf('The "%s" attribute is not present on the element "%s" in the "%s" region on the page %s', $attribute, $tag, $region, $this->getSession()->getCurrentUrl()));
      }
      if (strpos($attr, "$value") === FALSE) {
        throw new \Exception(sprintf('The "%s" attribute does not equal "%s" on the element "%s" in the "%s" region on the page %s', $attribute, $value, $tag, $region, $this->getSession()->getCurrentUrl()));
      }
    }
  }

  /**
   * @Then I( should) see :text in the :tag element with the :property CSS property set to :value in the :region( region)
   */
  public function assertRegionElementTextCss($text, $tag, $property, $value, $region) {
    $regionObj = $this->getRegion($region);
    $elements = $regionObj->findAll('css', $tag);
    if (empty($elements)) {
      throw new \Exception(sprintf('The element "%s" was not found in the "%s" region on the page %s', $tag, $region, $this->getSession()->getCurrentUrl()));
    }

    $found = FALSE;
    foreach ($elements as $element) {
      if ($element->getText() == $text) {
        $found = TRUE;
        break;
      }
    }
    if (!$found) {
      throw new \Exception(sprintf('The text "%s" was not found in the "%s" element in the "%s" region on the page %s', $text, $tag, $region, $this->getSession()->getCurrentUrl()));
    }

    $found = FALSE;
    if (!empty($property)) {
      $style = $element->getAttribute('style');
      $rules = explode(";", $style);
      foreach ($rules as $rule) {
        if (strpos($rule, $property) !== FALSE) {
          if (strpos($rule, $value) === FALSE) {
            throw new \Exception(sprintf('The "%s" style property does not equal "%s" on the element "%s" in the "%s" region on the page %s', $property, $value, $tag, $region, $this->getSession()->getCurrentUrl()));
          }
          $found = TRUE;
          break;
        }
      }
      if (!$found) {
        throw new \Exception(sprintf('The "%s" style property was not found in the "%s" element in the "%s" region on the page %s', $property, $tag, $region, $this->getSession()->getCurrentUrl()));
      }
    }
  }
}
