<?php

namespace Drupal\DrupalExtension\Context;

use Behat\Behat\Context\TranslatableContext;
use Behat\Mink\Exception\UnsupportedDriverActionException;
use Behat\MinkExtension\Context\MinkContext as MinkExtension;

/**
 * Extensions to the Mink Extension.
 */
class MinkContext extends MinkExtension implements TranslatableContext {

  /**
   * Returns list of definition translation resources paths.
   *
   * @return array
   */
  public static function getTranslationResources() {
    return self::getMinkTranslationResources() + glob(__DIR__ . '/../../../../i18n/*.xliff');
  }

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
   * Visit a given path, and additionally check for HTTP response code 200.
   *
   * @Given I am at :path
   * @When I visit :path
   *
   * @throws UnsupportedDriverActionException
   */
  public function assertAtPath($path) {
    $this->getSession()->visit($this->locatePath($path));

    // If available, add extra validation that this is a 200 response.
    try {
      $this->getSession()->getStatusCode();
      $this->assertHttpResponse('200');
    }
    catch (UnsupportedDriverActionException $e) {
      // Simply continue on, as this driver doesn't support HTTP response codes.
    }
  }

  /**
   * @When I click :link
   */
  public function assertClick($link) {
    // Use the Mink Extenstion step definition.
    $this->clickLink($link);
  }

  /**
   * @Given for :field I enter :value
   * @Given I enter :value for :field
   */
  public function assertEnterField($field, $value) {
    // Use the Mink Extenstion step definition.
    $this->fillField($field, $value);
  }

  /**
   * For javascript enabled scenarios, always wait for AJAX before clicking.
   *
   * @BeforeStep
   */
  public function beforeJavascriptStep($event) {
    /** @var \Behat\Behat\Hook\Scope\BeforeStepScope $event */
    $tags = $event->getFeature()->getTags();
    if (!in_array('javascript', $tags)) {
      return;
    }
    $text = $event->getStep()->getText();
    if (preg_match('/(follow|press|click|submit)/i', $text)) {
      $this->iWaitForAjaxToFinish();
    }
  }

  /**
   * For javascript enabled scenarios, always wait for AJAX after clicking.
   *
   * @AfterStep
   */
  public function afterJavascriptStep($event) {
    /** @var \Behat\Behat\Hook\Scope\BeforeStepScope $event */
    $tags = $event->getFeature()->getTags();
    if (!in_array('javascript', $tags)) {
      return;
    }
    $text = $event->getStep()->getText();
    if (preg_match('/(follow|press|click|submit)/i', $text)) {
      $this->iWaitForAjaxToFinish();
    }
  }

  /**
   * Wait for AJAX to finish.
   *
   * @Given I wait for AJAX to finish
   */
  public function iWaitForAjaxToFinish() {
    $this->getSession()->wait(5000, '(typeof(jQuery)=="undefined" || (0 === jQuery.active && 0 === jQuery(\':animated\').length))');
  }

  /**
   * Presses button with specified id|name|title|alt|value.
   *
   * @When I press the :button button
   */
  public function pressButton($button) {
    // Wait for any open autocomplete boxes to finish closing.  They block
    // form-submission if they are still open.
    // Use a step 'I press the "Esc" key in the "LABEL" field' to close
    // autocomplete suggestion boxes with Mink.  "Click" events on the
    // autocomplete suggestion do not work.
    try {
      $this->getSession()->wait(1000, 'typeof(jQuery)=="undefined" || jQuery("#autocomplete").length === 0');
    }
    catch (UnsupportedDriverActionException $e) {
      // The jQuery probably failed because the driver does not support
      // javascript.  That is okay, because if the driver does not support
      // javascript, it does not support autocomplete boxes either.
    }

    // Use the Mink Extension step definition.
    return parent::pressButton($button);
  }

  /**
   * @Given I press the :char key in the :field field
   *
   * @param mixed $char could be either char ('b') or char-code (98)
   * @throws \Exception
   */
  public function pressKey($char, $field) {
    static $keys = array(
      'backspace' => 8,
      'tab' => 9,
      'enter' => 13,
      'shift' => 16,
      'ctrl' =>  17,
      'alt' => 18,
      'pause' => 19,
      'break' => 19,
      'escape' =>  27,
      'esc' =>  27,
      'end' => 35,
      'home' =>  36,
      'left' => 37,
      'up' => 38,
      'right' =>39,
      'down' => 40,
      'insert' =>  45,
      'delete' =>  46,
      'pageup' => 33,
      'pagedown' => 34,
      'capslock' => 20,
    );

    if (is_string($char)) {
      if (strlen($char) < 1) {
        throw new \Exception('FeatureContext->keyPress($char, $field) was invoked but the $char parameter was empty.');
      }
      elseif (strlen($char) > 1) {
        // Support for all variations, e.g. ESC, Esc, page up, pageup.
        $char = $keys[strtolower(str_replace(' ', '', $char))];
      }
    }

    $element = $this->getSession()->getPage()->findField($field);
    if (!$element) {
      throw new \Exception("Field '$field' not found");
    }

    $driver = $this->getSession()->getDriver();
    // $driver->keyPress($element->getXpath(), $char);
    // This alternative to Driver->keyPress() handles cases that depend on
    // javascript which binds to key down/up events directly, such as Drupal's
    // autocomplete.js.
    $driver->keyDown($element->getXpath(), $char);
    $driver->keyUp($element->getXpath(), $char);
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
   * Links are not loaded on the page.
   *
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
   * Links are loaded but not visually visible (e.g they have display: hidden applied).
   *
   * @Then I should not visibly see the link :link
   */
  public function assertNotLinkVisuallyVisible($link) {
    $element = $this->getSession()->getPage();
    $result = $element->findLink($link);

    try {
      if ($result && $result->isVisible()) {
        throw new \Exception(sprintf("The link '%s' was visually visible on the page %s and was not supposed to be", $link, $this->getSession()->getCurrentUrl()));
      }
    }
    catch (UnsupportedDriverActionException $e) {
      // We catch the UnsupportedDriverActionException exception in case
      // this step is not being performed by a driver that supports javascript.
      // All other exceptions are valid.
    }

    if (!$result) {
      throw new \Exception(sprintf("The link '%s' was not loaded on the page %s at all", $link, $this->getSession()->getCurrentUrl()));
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
   * @Then I should not see the button :button
   * @Then I should not see the :button button
   */
  public function assertNotButton($button) {
    $element = $this->getSession()->getPage();
    $buttonObj = $element->findButton($button);
    if (!empty($buttonObj)) {
      throw new \Exception(sprintf("The button '%s' was found on the page %s", $button, $this->getSession()->getCurrentUrl()));
    }
  }

  /**
   * @When I follow/click :link in the :region( region)
   *
   * @throws \Exception
   *   If region or link within it cannot be found.
   */
  public function assertRegionLinkFollow($link, $region) {
    $regionObj = $this->getRegion($region);

    // Find the link within the region
    $linkObj = $regionObj->findLink($link);
    if (empty($linkObj)) {
      throw new \Exception(sprintf('The link "%s" was not found in the region "%s" on the page %s', $link, $region, $this->getSession()->getCurrentUrl()));
    }
    $linkObj->click();
  }

  /**
   * Checks, if a button with id|name|title|alt|value exists or not and pressess the same
   *
   * @Given I press :button in the :region( region)
   *
   * @param $button
   *   string The id|name|title|alt|value of the button to be pressed
   * @param $region
   *   string The region in which the button should be pressed
   *
   * @throws \Exception
   *   If region or button within it cannot be found.
   */
  public function assertRegionPressButton($button, $region) {
    $regionObj = $this->getRegion($region);

    $buttonObj = $regionObj->findButton($button);
    if (empty($buttonObj)) {
      throw new \Exception(sprintf("The button '%s' was not found in the region '%s' on the page %s", $button, $region, $this->getSession()->getCurrentUrl()));
    }
    $regionObj->pressButton($button);
  }

  /**
   * Fills in a form field with id|name|title|alt|value in the specified region.
   *
   * @Given I fill in :value for :field in the :region( region)
   * @Given I fill in :field with :value in the :region( region)
   *
   * @throws \Exception
   *   If region cannot be found.
   */
  public function regionFillField($field, $value, $region) {
    $field = $this->fixStepArgument($field);
    $value = $this->fixStepArgument($value);
    $regionObj = $this->getRegion($region);
    $regionObj->fillField($field, $value);
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
   * @Then I (should )see the text :text
   */
  public function assertTextVisible($text) {
    // Use the Mink Extension step definition.
    $this->assertPageContainsText($text);
  }

  /**
   * @Then I should not see the text :text
   */
  public function assertNotTextVisible($text) {
    // Use the Mink Extension step definition.
    $this->assertPageNotContainsText($text);
  }

  /**
   * @Then I should get a :code HTTP response
   */
  public function assertHttpResponse($code) {
    // Use the Mink Extension step definition.
    $this->assertResponseStatus($code);
  }

  /**
   * @Then I should not get a :code HTTP response
   */
  public function assertNotHttpResponse($code) {
    // Use the Mink Extension step definition.
    $this->assertResponseStatusIsNot($code);
  }

  /**
   * @Given I check the box :checkbox
   */
  public function assertCheckBox($checkbox) {
    // Use the Mink Extension step definition.
    $this->checkOption($checkbox);
  }

  /**
   * @Given I uncheck the box :checkbox
   */
  public function assertUncheckBox($checkbox) {
    // Use the Mink Extension step definition.
    $this->uncheckOption($checkbox);
  }

  /**
   * @When I select the radio button :label with the id :id
   * @When I select the radio button :label
   *
   * @TODO convert to mink extension.
   */
  public function assertSelectRadioById($label, $id = '') {
    $element = $this->getSession()->getPage();
    $radiobutton = $id ? $element->findById($id) : $element->find('named', array('radio', $this->getSession()->getSelectorsHandler()->xpathLiteral($label)));
    if ($radiobutton === NULL) {
      throw new \Exception(sprintf('The radio button with "%s" was not found on the page %s', $id ? $id : $label, $this->getSession()->getCurrentUrl()));
    }
    $value = $radiobutton->getAttribute('value');
    $labelonpage = $radiobutton->getParent()->getText();
    if ($label != $labelonpage) {
      throw new \Exception(sprintf("Button with id '%s' has label '%s' instead of '%s' on the page %s", $id, $labelonpage, $label, $this->getSession()->getCurrentUrl()));
    }
    $radiobutton->selectOption($value, FALSE);
  }

  /**
   * @} End of defgroup "mink extensions"
   */


}
