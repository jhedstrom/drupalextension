<?php

declare(strict_types=1);

namespace Drupal\DrupalExtension\Context;

use Behat\Step\Given;
use Behat\Step\When;
use Behat\Hook\BeforeStep;
use Behat\Hook\AfterStep;
use Behat\Step\Then;
use Behat\Behat\Hook\Scope\BeforeStepScope;
use Behat\Behat\Hook\Scope\AfterStepScope;
use Behat\Behat\Context\TranslatableContext;
use Behat\Mink\Exception\ElementNotFoundException;
use Behat\Mink\Exception\ExpectationException;
use Behat\Mink\Exception\UnsupportedDriverActionException;
use Behat\Mink\Selector\Xpath\Escaper;
use Behat\MinkExtension\Context\MinkContext as MinkExtension;
use Drupal\DrupalExtension\RegionTrait;
use Drupal\DrupalExtension\TagTrait;

/**
 * Extensions to the Mink Extension.
 */
class MinkContext extends MinkExtension implements TranslatableContext {

  use RegionTrait;
  use TagTrait;

  /**
   * Returns list of definition translation resources paths.
   *
   * @return array<int, string>
   *   List of translation resource paths.
   */
  public static function getTranslationResources(): array {
    return array_merge(
      self::getMinkTranslationResources(),
      glob(__DIR__ . '/../../../../i18n/*.xliff') ?: [],
    );
  }

  /**
   * Visit a given path, and additionally check for HTTP response code 200.
   *
   * @code
   * Given I am at "/node/1"
   * @endcode
   *
   * @throws \Behat\Mink\Exception\UnsupportedDriverActionException
   */
  #[Given('I am at :path')]
  public function iAmAtPath(string $path): void {
    $this->visitPathWithStatusCheck($path);
  }

  /**
   * Visit a given path without asserting any HTTP status.
   *
   * Use 'Then I should get a :code HTTP response' to assert status separately.
   *
   * @code
   * When I visit "/node/1"
   * @endcode
   */
  #[When('I visit :path')]
  public function iVisitPath(string $path): void {
    $this->getSession()->visit($this->locatePath($path));
  }

  /**
   * Click a link by its text.
   *
   * @code
   * When I click "Read more"
   * @endcode
   */
  #[When('I click :link')]
  public function iClickLink(string $link): void {
    // Use the Mink Extension step definition.
    $this->clickLink($link);
  }

  /**
   * Enter a value into a form field.
   *
   * @code
   * Given for "Title" I enter "My article"
   * @endcode
   */
  #[Given('for :field I enter :value')]
  public function iEnterValueInField(string $field, string $value): void {
    // Use the Mink Extension step definition.
    $this->fillField($field, $value);
  }

  /**
   * Enter a value into a form field (alternative phrasing).
   *
   * @code
   * Given I enter "My article" for "Title"
   * @endcode
   */
  #[Given('I enter :value for :field')]
  public function iEnterValueForField(string $value, string $field): void {
    // Use the Mink Extension step definition.
    $this->fillField($field, $value);
  }

  /**
   * For javascript enabled scenarios, always wait for AJAX before clicking.
   */
  #[BeforeStep]
  public function beforeJavascriptStep(BeforeStepScope $event): void {
    /** @var \Behat\Behat\Hook\Scope\BeforeStepScope $event */
    // Make sure the feature is registered in case this hook fires before
    // ::registerFeature() which is also a @BeforeStep. Behat doesn't
    // support ordering hooks.
    $this->registerFeature($event);
    if (!$this->hasTag('javascript')) {
      return;
    }
    $text = $event->getStep()->getText();
    if (preg_match('/\b(follow|press|click|submit|attach)\b/i', $text)) {
      $this->iWaitForAjaxToFinish($event);
    }
  }

  /**
   * For javascript enabled scenarios, always wait for AJAX after clicking.
   */
  #[AfterStep]
  public function afterJavascriptStep(AfterStepScope $event): void {
    if (!$this->hasTag('javascript')) {
      return;
    }
    $text = $event->getStep()->getText();
    if (preg_match('/\b(follow|press|click|submit|attach)\b/i', $text)) {
      $this->iWaitForAjaxToFinish($event);
    }
  }

  /**
   * Wait for AJAX to finish.
   *
   * @see \Drupal\FunctionalJavascriptTests\JSWebAssert::assertWaitOnAjaxRequest()
   *
   * @code
   * Given I wait for AJAX to finish
   * @endcode
   */
  #[Given('I wait for AJAX to finish')]
  public function iWaitForAjaxToFinish(mixed $event = NULL): void {
    if (!$this->getSession()->isStarted()) {
      return;
    }

    $condition = <<<JS
    (function() {
      function isAjaxing(instance) {
        return instance && instance.ajaxing === true;
      }
      var d7_not_ajaxing = true;
      if (typeof Drupal !== 'undefined' && typeof Drupal.ajax !== 'undefined' && typeof Drupal.ajax.instances === 'undefined') {
        for(var i in Drupal.ajax) { if (isAjaxing(Drupal.ajax[i])) { d7_not_ajaxing = false; } }
      }
      var d8_not_ajaxing = (typeof Drupal === 'undefined' || typeof Drupal.ajax === 'undefined' || typeof Drupal.ajax.instances === 'undefined' || !Drupal.ajax.instances.some(isAjaxing))
      return (
        // Assert no AJAX request is running (via jQuery or Drupal) and no
        // animation is running.
        (typeof jQuery === 'undefined' || jQuery.hasOwnProperty('active') === false || (jQuery.active <= 0 && jQuery(':animated').length === 0)) &&
        d7_not_ajaxing && d8_not_ajaxing
      );
    }());
JS;
    $ajax_timeout = $this->getMinkParameter('ajax_timeout');
    $result = $this->getSession()->wait(1000 * $ajax_timeout, $condition);
    if (!$result) {
      if ($ajax_timeout === NULL) {
        throw new \RuntimeException('No AJAX timeout has been defined. Please verify that "Drupal\MinkExtension" is configured in behat.yml (and not "Behat\MinkExtension").');
      }
      if ($event) {
        /** @var \Behat\Behat\Hook\Scope\BeforeStepScope $event */
        $event_data = ' ' . json_encode([
          'name' => $event->getName(),
          'feature' => $event->getFeature()->getTitle(),
          'step' => $event->getStep()->getText(),
          'suite' => $event->getSuite()->getName(),
        ]);
      }
      else {
        $event_data = '';
      }
      throw new \RuntimeException('Unable to complete AJAX request.' . $event_data);
    }
  }

  /**
   * Presses button with specified id|name|title|alt|value.
   *
   * @code
   * When I press the "Save" button
   * @endcode
   */
  #[When('I press the :button button')]
  public function pressButton(mixed $button): void {
    // Wait for any open autocomplete boxes to finish closing.  They block
    // form-submission if they are still open.
    // Use a step 'I press the "Esc" key in the "LABEL" field' to close
    // autocomplete suggestion boxes with Mink.  "Click" events on the
    // autocomplete suggestion do not work.
    try {
      $this->getSession()->wait(1000, 'typeof(jQuery)=="undefined" || jQuery("#autocomplete").length === 0');
    }
    catch (UnsupportedDriverActionException) {
      // The jQuery probably failed because the driver does not support
      // javascript.  That is okay, because if the driver does not support
      // javascript, it does not support autocomplete boxes either.
    }

    // Use the Mink Extension step definition.
    parent::pressButton($button);
  }

  /**
   * Press a key in a form field.
   *
   * @param mixed $char
   *   Could be either char ('b') or char-code (98).
   * @param string $field
   *   The field to press the key in.
   *
   * @throws \Exception
   *
   * @code
   *   Given I press the "enter" key in the "Search" field
   * @endcode
   */
  #[Given('I press the :char key in the :field field')]
  public function pressKey(mixed $char, string $field): void {
    static $keys = [
      'backspace' => 8,
      'tab' => 9,
      'enter' => 13,
      'shift' => 16,
      'ctrl' => 17,
      'alt' => 18,
      'pause' => 19,
      'break' => 19,
      'escape' => 27,
      'esc' => 27,
      'end' => 35,
      'home' => 36,
      'left' => 37,
      'up' => 38,
      'right' => 39,
      'down' => 40,
      'insert' => 45,
      'delete' => 46,
      'pageup' => 33,
      'pagedown' => 34,
      'capslock' => 20,
    ];

    if (is_string($char)) {
      if (strlen($char) < 1) {
        throw new \RuntimeException('FeatureContext->keyPress($char, $field) was invoked but the $char parameter was empty.');
      }
      if (strlen($char) > 1) {
        // Support for all variations, e.g. ESC, Esc, page up, pageup.
        $char = $keys[strtolower(str_replace(' ', '', $char))];
      }
    }

    $element = $this->getSession()->getPage()->findField($field);
    if (!$element) {
      throw new ElementNotFoundException($this->getSession()->getDriver(), 'field', 'id|name|label|value|placeholder', $field);
    }

    $driver = $this->getSession()->getDriver();
    // Use keyDown/keyUp instead of keyPress to handle javascript that binds
    // to key down/up events directly, such as Drupal's autocomplete.js.
    $driver->keyDown($element->getXpath(), $char);
    $driver->keyUp($element->getXpath(), $char);
  }

  /**
   * Drag and drop one element onto another.
   *
   * @code
   * When I drag element "#draggable" onto element "#droppable"
   * @endcode
   *
   * @throws \Behat\Mink\Exception\ElementNotFoundException
   */
  #[When('I drag element :source onto element :target')]
  public function dragElementOntoAnother(string $source, string $target): void {
    $page = $this->getSession()->getPage();
    $driver = $this->getSession()->getDriver();

    $source_element = $page->find('css', $source);
    if ($source_element === NULL) {
      throw new ElementNotFoundException($driver, 'source element', 'css selector', $source);
    }

    $target_element = $page->find('css', $target);
    if ($target_element === NULL) {
      throw new ElementNotFoundException($driver, 'target element', 'css selector', $target);
    }

    $source_element->dragTo($target_element);
  }

  /**
   * Assert a link is visible on the page.
   *
   * @code
   * Then I should see the link "Log out"
   * @endcode
   */
  #[Then('I should see the link :link')]
  public function linkAssertIsVisible(string $link): void {
    $element = $this->getSession()->getPage();
    $result = $element->findLink($link);
    $driver = $this->getSession()->getDriver();

    try {
      if ($result && !$result->isVisible()) {
        throw new ElementNotFoundException($driver, 'link', 'id|title|alt|text', $link);
      }
    }
    catch (UnsupportedDriverActionException) {
      // We catch the UnsupportedDriverActionException exception in case
      // this step is not being performed by a driver that supports javascript.
      // All other exceptions are valid.
    }

    if (empty($result)) {
      throw new ElementNotFoundException($driver, 'link', 'id|title|alt|text', $link);
    }
  }

  /**
   * Links are not loaded on the page.
   *
   * @code
   * Then I should not see the link "Log out"
   * @endcode
   */
  #[Then('I should not see the link :link')]
  public function linkAssertIsNotVisible(string $link): void {
    $element = $this->getSession()->getPage();
    $result = $element->findLink($link);
    $driver = $this->getSession()->getDriver();

    try {
      if ($result && $result->isVisible()) {
        throw new ExpectationException(sprintf("The link '%s' was present on the page %s and was not supposed to be", $link, $this->getSession()->getCurrentUrl()), $driver);
      }
    }
    catch (UnsupportedDriverActionException) {
      // We catch the UnsupportedDriverActionException exception in case
      // this step is not being performed by a driver that supports javascript.
      // All other exceptions are valid.
    }

    if ($result) {
      throw new ExpectationException(sprintf("The link '%s' was present on the page %s and was not supposed to be", $link, $this->getSession()->getCurrentUrl()), $driver);
    }
  }

  /**
   * Links are loaded but not visually visible.
   *
   * For example, they have 'display: hidden' applied.
   *
   * @code
   * Then I should not visibly see the link "Skip to main content"
   * @endcode
   */
  #[Then('I should not visibly see the link :link')]
  public function linkAssertIsNotVisuallyVisible(string $link): void {
    $element = $this->getSession()->getPage();
    $result = $element->findLink($link);
    $driver = $this->getSession()->getDriver();

    try {
      if ($result && $result->isVisible()) {
        throw new ExpectationException(sprintf("The link '%s' was visually visible on the page %s and was not supposed to be", $link, $this->getSession()->getCurrentUrl()), $driver);
      }
    }
    catch (UnsupportedDriverActionException) {
      // We catch the UnsupportedDriverActionException exception in case
      // this step is not being performed by a driver that supports javascript.
      // All other exceptions are valid.
    }

    if (!$result) {
      throw new ElementNotFoundException($driver, 'link', 'id|title|alt|text', $link);
    }
  }

  /**
   * Assert a heading is visible on the page.
   *
   * @code
   * Then I should see the heading "Welcome"
   * @endcode
   */
  #[Then('I should see the heading :heading')]
  public function headingAssertIsVisible(string $heading): void {
    $element = $this->getSession()->getPage();
    foreach ($element->findAll('css', 'h1, h2, h3, h4, h5, h6') as $result) {
      if ($result->getText() == $heading) {
        return;
      }
    }
    throw new ElementNotFoundException($this->getSession()->getDriver(), 'heading', 'text', $heading);
  }

  /**
   * Assert a heading is not on the page.
   *
   * @code
   * Then I should not see the heading "Error"
   * @endcode
   */
  #[Then('I should not see the heading :heading')]
  public function headingAssertIsNotVisible(string $heading): void {
    $element = $this->getSession()->getPage();
    foreach ($element->findAll('css', 'h1, h2, h3, h4, h5, h6') as $result) {
      if ($result->getText() == $heading) {
        throw new ExpectationException(sprintf("The text '%s' was found in a heading on the page %s", $heading, $this->getSession()->getCurrentUrl()), $this->getSession()->getDriver());
      }
    }
  }

  /**
   * Assert a button is visible on the page.
   *
   * @code
   * Then I should see the button "Save"
   * @endcode
   */
  #[Then('I should see the button :button')]
  public function buttonAssertIsVisible(string $button): void {
    $this->assertButtonExists($button);
  }

  /**
   * Assert a button (with the noun before "button") is visible on the page.
   *
   * @code
   * Then I should see the "Save" button
   * @endcode
   */
  #[Then('I should see the :button button')]
  public function buttonAssertIsVisibleByLabel(string $button): void {
    $this->assertButtonExists($button);
  }

  /**
   * Assert a button is not on the page.
   *
   * @code
   * Then I should not see the button "Delete"
   * @endcode
   */
  #[Then('I should not see the button :button')]
  public function buttonAssertIsNotVisible(string $button): void {
    $this->assertButtonDoesNotExist($button);
  }

  /**
   * Assert a button (with the noun before "button") is not on the page.
   *
   * @code
   * Then I should not see the "Delete" button
   * @endcode
   */
  #[Then('I should not see the :button button')]
  public function buttonAssertIsNotVisibleByLabel(string $button): void {
    $this->assertButtonDoesNotExist($button);
  }

  /**
   * Follow a link in a specific region.
   *
   * @throws \Exception
   *   If region or link within it cannot be found.
   *
   * @code
   * When I follow "Read more" in the "content"
   * When I follow "Read more" in the "content" region
   * When I click "Read more" in the "content" region
   * @endcode
   */
  #[When('I follow/click :link in the :region( region)')]
  public function iFollowLinkInRegion(string $link, string $region): void {
    $region_obj = $this->getRegion($region);

    // Find the link within the region.
    $link_obj = $region_obj->findLink($link);
    if (empty($link_obj)) {
      throw new ElementNotFoundException($this->getSession()->getDriver(), sprintf('link in the "%s" region', $region), 'id|title|alt|text', $link);
    }
    $link_obj->click();
  }

  /**
   * Checks if a button exists and presses it.
   *
   * Matches by id|name|title|alt|value.
   *
   * @param string $button
   *   The id|name|title|alt|value of the button to be pressed.
   * @param string $region
   *   The region in which the button should be pressed.
   *
   * @throws \Exception
   *   If region or button within it cannot be found.
   *
   * @code
   * Given I press "Submit" in the "sidebar"
   * Given I press "Submit" in the "sidebar" region
   * @endcode
   */
  #[Given('I press :button in the :region( region)')]
  public function iPressButtonInRegion(string $button, string $region): void {
    $region_obj = $this->getRegion($region);

    $button_obj = $region_obj->findButton($button);
    if (empty($button_obj)) {
      throw new ElementNotFoundException($this->getSession()->getDriver(), sprintf('button in the "%s" region', $region), 'id|name|title|alt|value', $button);
    }
    $button_obj->press();
  }

  /**
   * Fills in a form field with id|name|title|alt|value in the specified region.
   *
   * @throws \Exception
   *   If region cannot be found.
   *
   * @code
   * Given I fill in "test" for "Search" in the "header"
   * Given I fill in "test" for "Search" in the "header" region
   * @endcode
   */
  #[Given('I fill in :value for :field in the :region( region)')]
  public function iFillInValueForFieldInRegion(string $value, string $field, string $region): void {
    $this->fillFieldInRegion($field, $value, $region);
  }

  /**
   * Fills in a form field (alternative phrasing) in the specified region.
   *
   * @throws \Exception
   *   If region cannot be found.
   *
   * @code
   * Given I fill in "Search" with "test" in the "header" region
   * @endcode
   */
  #[Given('I fill in :field with :value in the :region( region)')]
  public function iFillInFieldWithValueInRegion(string $field, string $value, string $region): void {
    $this->fillFieldInRegion($field, $value, $region);
  }

  /**
   * Checks if a checkbox exists and checks it.
   *
   * Matches by id|name|title|alt|value.
   *
   * @param string $locator
   *   The id|name|title|alt|value of the checkbox to be checked.
   * @param string $region
   *   The region in which the checkbox should be checked.
   *
   * @throws \Exception
   *   If region or checkbox within it cannot be found.
   *
   * @code
   * Given I check "Published" in the "content"
   * Given I check "Published" in the "content" region
   * @endcode
   */
  #[Given('I check :locator in the :region( region)')]
  public function iCheckLocatorInRegion(string $locator, string $region): void {
    $region_obj = $this->getRegion($region);
    $region_obj->checkField($locator);
  }

  /**
   * Checks if a checkbox exists and unchecks it.
   *
   * Matches by id|name|title|alt|value.
   *
   * @param string $checkbox
   *   The id|name|title|alt|value of the checkbox to be unchecked.
   * @param string $region
   *   The region in which the checkbox should be unchecked.
   *
   * @throws \Exception
   *   If region or checkbox within it cannot be found.
   *
   * @code
   * Given I uncheck "Promoted" in the "content"
   * Given I uncheck "Promoted" in the "content" region
   * @endcode
   */
  #[Given('I uncheck :checkbox in the :region( region)')]
  public function iUncheckLocatorInRegion(string $checkbox, string $region): void {
    $region_obj = $this->getRegion($region);
    $region_obj->uncheckField($checkbox);
  }

  /**
   * Find a heading in a specific region.
   *
   * @throws \Exception
   *   If region or header within it cannot be found.
   *
   * @code
   * Then I should see the heading "Latest news" in the "sidebar"
   * Then I should see the heading "Latest news" in the "sidebar" region
   * @endcode
   */
  #[Then('I should see the heading :heading in the :region( region)')]
  public function regionHeadingAssertIsVisible(string $heading, string $region): void {
    $this->assertRegionContainsHeading($heading, $region);
  }

  /**
   * Find a heading (with the noun before "heading") in a specific region.
   *
   * @throws \Exception
   *   If region or header within it cannot be found.
   *
   * @code
   * Then I should see the "Latest news" heading in the "sidebar"
   * Then I should see the "Latest news" heading in the "sidebar" region
   * @endcode
   */
  #[Then('I should see the :heading heading in the :region( region)')]
  public function regionHeadingAssertIsVisibleByLabel(string $heading, string $region): void {
    $this->assertRegionContainsHeading($heading, $region);
  }

  /**
   * Assert a link exists in a region.
   *
   * @throws \Exception
   *   If region or link within it cannot be found.
   *
   * @code
   * Then I should see the link "About us" in the "footer"
   * Then I should see the link "About us" in the "footer" region
   * @endcode
   */
  #[Then('I should see the link :link in the :region( region)')]
  public function regionLinkAssertIsVisible(string $link, string $region): void {
    $region_obj = $this->getRegion($region);

    $result = $region_obj->findLink($link);
    if (empty($result)) {
      throw new ElementNotFoundException($this->getSession()->getDriver(), sprintf('link in the "%s" region', $region), 'id|title|alt|text', $link);
    }
  }

  /**
   * Assert a link does not exist in a region.
   *
   * @throws \Exception
   *   If region or link within it cannot be found.
   *
   * @code
   * Then I should not see the link "Admin" in the "footer"
   * Then I should not see the link "Admin" in the "footer" region
   * @endcode
   */
  #[Then('I should not see the link :link in the :region( region)')]
  public function regionLinkAssertIsNotVisible(string $link, string $region): void {
    $region_obj = $this->getRegion($region);

    $result = $region_obj->findLink($link);
    if (!empty($result)) {
      throw new ExpectationException(sprintf('Link to "%s" in the "%s" region on the page %s', $link, $region, $this->getSession()->getCurrentUrl()), $this->getSession()->getDriver());
    }
  }

  /**
   * Assert text is visible in a region.
   *
   * @throws \Exception
   *   If region or text within it cannot be found.
   *
   * @code
   * Then I should see "Welcome" in the "content"
   * Then I should see "Welcome" in the "content" region
   * Then I should see the text "Welcome" in the "content" region
   * @endcode
   */
  #[Then('I should see( the text) :text in the :region( region)')]
  public function regionTextAssertIsVisible(string $text, string $region): void {
    $this->assertRegionContainsText($text, $region);
  }

  /**
   * Assert text is not visible in a region.
   *
   * @throws \Exception
   *   If region or text within it cannot be found.
   *
   * @code
   * Then I should not see "Error" in the "content"
   * Then I should not see "Error" in the "content" region
   * Then I should not see the text "Error" in the "content" region
   * @endcode
   */
  #[Then('I should not see( the text) :text in the :region( region)')]
  public function regionTextAssertIsNotVisible(string $text, string $region): void {
    $this->assertRegionDoesNotContainText($text, $region);
  }

  /**
   * Assert text is visible on the page.
   *
   * @code
   * Then I should see the text "Welcome to Drupal"
   * @endcode
   */
  #[Then('I should see the text :text')]
  public function textAssertIsVisible(string $text): void {
    // Use the Mink Extension step definition.
    $this->assertPageContainsText($text);
  }

  /**
   * Assert text is not visible on the page.
   *
   * @code
   * Then I should not see the text "Access denied"
   * @endcode
   */
  #[Then('I should not see the text :text')]
  public function textAssertIsNotVisible(string $text): void {
    // Use the Mink Extension step definition.
    $this->assertPageNotContainsText($text);
  }

  /**
   * Assert the HTTP response code.
   *
   * @code
   * Then I should get a 200 HTTP response
   * @endcode
   */
  #[Then('I should get a :code HTTP response')]
  public function httpResponseAssertEquals(int|string $code): void {
    // Use the Mink Extension step definition.
    $this->assertResponseStatus($code);
  }

  /**
   * Assert the HTTP response code is not a specific value.
   *
   * @code
   * Then I should not get a :code HTTP response
   * @endcode
   */
  #[Then('I should not get a :code HTTP response')]
  public function httpResponseAssertNotEquals(int|string $code): void {
    // Use the Mink Extension step definition.
    $this->assertResponseStatusIsNot($code);
  }

  /**
   * Check a checkbox.
   *
   * @code
   * Given I check the box "Published"
   * @endcode
   */
  #[Given('I check the box :checkbox')]
  public function iCheckTheBox(string $checkbox): void {
    // Use the Mink Extension step definition.
    $this->checkOption($checkbox);
  }

  /**
   * Uncheck a checkbox.
   *
   * @code
   * Given I uncheck the box "Promoted to front page"
   * @endcode
   */
  #[Given('I uncheck the box :checkbox')]
  public function iUncheckTheBox(string $checkbox): void {
    // Use the Mink Extension step definition.
    $this->uncheckOption($checkbox);
  }

  /**
   * Select a radio button.
   *
   * @code
   * When I select the radio button "Full HTML"
   * @endcode
   */
  #[When('I select the radio button :label')]
  public function iSelectRadioByLabel(string $label): void {
    $this->selectRadioButton($label, '');
  }

  /**
   * Select a radio button by id.
   *
   * @code
   * When I select the radio button "Full HTML" with the id "edit-format-full-html"
   * @endcode
   */
  #[When('I select the radio button :label with the id :id')]
  public function iSelectRadioByLabelWithId(string $label, string $id): void {
    $this->selectRadioButton($label, $id);
  }

  /**
   * Expand/collapse/toggle a <details> element by <summary> text.
   *
   * @code
   * When I expand details labelled "Advanced settings"
   * When I collapse details labelled "Advanced settings"
   * When I click details labelled "Advanced settings"
   * @endcode
   */
  #[When('I :action details labelled :summary')]
  public function iExpandOrCollapseDetailsByLabel(string $action, string $summary): void {
    $page = $this->getSession()->getPage();

    $action = strtolower(trim($action));
    $escaper = new Escaper();
    $literal = $escaper->escapeLiteral($summary);

    if ($action === 'expand') {
      $expanded_state = "[not(@open)]";
    }
    elseif ($action === 'collapse') {
      $expanded_state = "[@open]";
    }
    elseif ($action === 'click') {
      $expanded_state = '';
    }
    else {
      throw new \InvalidArgumentException(sprintf("Unknown action '%s'. Expected expand, collapse, or click.", $action));
    }

    $xpath = sprintf('//details%s/summary[normalize-space()][contains(normalize-space(.), %s)]', $expanded_state, $literal);

    $element = $page->find('xpath', $xpath);
    if (!$element) {
      throw new ElementNotFoundException($this->getSession()->getDriver(), sprintf('details element for "%s" action', $action), 'summary text', $summary);
    }

    $ajax_timeout = $this->getMinkParameter('ajax_timeout') ?? 5;
    // 1/10th of ajax_timeout, in microseconds.
    $animate_delay = $ajax_timeout * 100000;
    try {
      $element->click();
      usleep($animate_delay);
    }
    catch (UnsupportedDriverActionException) {
      // Goutte etc only supports clicking link, submit, button;
      // for non-JS drivers this won't impact test.
    }
  }

  /**
   * Visit a path and verify the response is HTTP 200 when supported.
   */
  protected function visitPathWithStatusCheck(string $path): void {
    $this->getSession()->visit($this->locatePath($path));

    // If available, add extra validation that this is a 200 response.
    try {
      $this->getSession()->getStatusCode();
      $this->httpResponseAssertEquals('200');
    }
    catch (UnsupportedDriverActionException) {
      // Simply continue on, as this driver doesn't support HTTP response codes.
    }
  }

  /**
   * Assert the named button is present anywhere on the page.
   */
  protected function assertButtonExists(string $button): void {
    $element = $this->getSession()->getPage();
    $button_obj = $element->findButton($button);
    if (empty($button_obj)) {
      throw new ElementNotFoundException($this->getSession()->getDriver(), 'button', 'id|name|title|alt|value', $button);
    }
  }

  /**
   * Assert the named button is not present anywhere on the page.
   */
  protected function assertButtonDoesNotExist(string $button): void {
    $element = $this->getSession()->getPage();
    $button_obj = $element->findButton($button);
    if (!empty($button_obj)) {
      throw new ExpectationException(sprintf("The button '%s' was found on the page %s", $button, $this->getSession()->getCurrentUrl()), $this->getSession()->getDriver());
    }
  }

  /**
   * Assert that a heading is present in a region.
   */
  protected function assertRegionContainsHeading(string $heading, string $region): void {
    $region_obj = $this->getRegion($region);

    foreach ($region_obj->findAll('css', 'h1, h2, h3, h4, h5, h6') as $element) {
      if (trim($element->getText()) === $heading) {
        return;
      }
    }

    throw new ElementNotFoundException($this->getSession()->getDriver(), sprintf('heading in the "%s" region', $region), 'text', $heading);
  }

  /**
   * Assert that text is present in a region.
   */
  protected function assertRegionContainsText(string $text, string $region): void {
    $region_obj = $this->getRegion($region);

    $region_text = $region_obj->getText();
    if (!str_contains($region_text, $text)) {
      throw new ExpectationException(sprintf("The text '%s' was not found in the region '%s' on the page %s", $text, $region, $this->getSession()->getCurrentUrl()), $this->getSession()->getDriver());
    }
  }

  /**
   * Assert that text is not present in a region.
   */
  protected function assertRegionDoesNotContainText(string $text, string $region): void {
    $region_obj = $this->getRegion($region);

    $region_text = $region_obj->getText();
    if (str_contains($region_text, $text)) {
      throw new ExpectationException(sprintf('The text "%s" was found in the region "%s" on the page %s', $text, $region, $this->getSession()->getCurrentUrl()), $this->getSession()->getDriver());
    }
  }

  /**
   * Fill a form field within a specific region.
   */
  protected function fillFieldInRegion(string $field, string $value, string $region): void {
    $field = $this->fixStepArgument($field);
    $value = $this->fixStepArgument($value);
    $region_obj = $this->getRegion($region);
    $region_obj->fillField($field, $value);
  }

  /**
   * Select a radio button by label, optionally locating it via id first.
   */
  protected function selectRadioButton(string $label, string $id): void {
    $element = $this->getSession()->getPage();
    $radiobutton = $id !== '' && $id !== '0' ? $element->findById($id) : $element->find('named', [
      'radio',
      $label,
    ]);
    if ($radiobutton === NULL) {
      throw new ElementNotFoundException($this->getSession()->getDriver(), 'radio button', $id !== '' && $id !== '0' ? 'id' : 'label', $id ?: $label);
    }
    $value = $radiobutton->getAttribute('value');
    $radio_id = $radiobutton->getAttribute('id');
    $label_element = $radio_id !== NULL ? $element->find('css', sprintf("label[for='%s']", $radio_id)) : NULL;
    if ($label_element !== NULL) {
      $labelonpage = $label_element->getText();
      if ($label != $labelonpage) {
        throw new ExpectationException(sprintf("Button with id '%s' has label '%s' instead of '%s' on the page %s", $id, $labelonpage, $label, $this->getSession()->getCurrentUrl()), $this->getSession()->getDriver());
      }
    }
    $radiobutton->selectOption($value, FALSE);
  }

}
