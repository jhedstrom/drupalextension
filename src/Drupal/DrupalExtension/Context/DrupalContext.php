<?php

namespace Drupal\DrupalExtension\Context;

use Behat\MinkExtension\Context\MinkContext;
use Behat\Behat\Exception\PendingException;
use Behat\Behat\Event\ScenarioEvent;
use Behat\Mink\Element\Element;
use Behat\Mink\Exception\UnsupportedDriverActionException;

use Drupal\Drupal;
use Drupal\DrupalExtension\Event\EntityEvent;
use Drupal\DrupalExtension\Context\DrupalSubContextInterface;

use Drupal\Exception\Exception;
use Symfony\Component\Process\Process;
use Symfony\Component\EventDispatcher\EventDispatcher;

use Behat\Behat\Context\Step\Given;
use Behat\Behat\Context\Step\When;
use Behat\Behat\Context\Step\Then;
use Behat\Behat\Context\TranslatedContextInterface;

use Behat\Gherkin\Node\PyStringNode;
use Behat\Gherkin\Node\TableNode;

use Behat\Mink\Driver\Selenium2Driver as Selenium2Driver;

/**
 * Features context.
 */
class DrupalContext extends MinkContext implements DrupalAwareInterface, TranslatedContextInterface {

  private $drupal, $drupalParameters;

  /**
   * Event dispatcher object.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcher
   */
  protected $dispatcher;

  /**
   * Basic auth user and password.
   *
   * @var array
   */
  public $basic_auth;

  /**
   * Keep track of nodes so they can be cleaned up.
   *
   * @var array
   */
  protected $nodes = array();

  /**
   * Current authenticated user.
   *
   * A value of FALSE denotes an anonymous user.
   *
   * @var mixed
   */
  protected $user = FALSE;

  /**
   * Keep track of all users that are created so they can easily be removed.
   *
   * @var array
   */
  protected $users = array();

  /**
   * Keep track of all terms that are created so they can easily be removed.
   *
   * @var array
   */
  protected $terms = array();

  /**
   * Keep track of any roles that are created so they can easily be removed.
   *
   * @var array
   */
  protected $roles = array();

  /**
   * Keep track of drush output.
   *
   * @var string
   */
  private $drushOutput;

  /**
   * Initialize subcontexts.
   *
   * @param array $subcontexts
   *   Array of sub-context class names to initiate, keyed by sub-context alias.
   */
  public function initializeSubContexts() {

    $classes = get_declared_classes();
    $subcontext_classes = array();
    foreach ($classes as $class) {
      $reflect = new \ReflectionClass($class);
      if ($reflect->implementsInterface('Drupal\DrupalExtension\Context\DrupalSubContextInterface')) {
        $alias = $class::getAlias();
        $this->useContext($alias, new $class);
      }
    }
  }

  /**
   * Returns list of definition translation resources paths.
   *
   * @return array
   */
  public function getTranslationResources() {
      $translationFilesParent = parent::getTranslationResources();
      $translationFilesDrupal = glob(__DIR__ . '/../../../../i18n/*.xliff');
      $translationFilesCombined = array_merge($translationFilesParent, $translationFilesDrupal);
      return $translationFilesCombined;
  }

  /**
   * Set Drupal instance.
   */
  public function setDrupal(Drupal $drupal) {
    $this->drupal = $drupal;
  }

  /**
   * Get Drupal instance.
   */
  public function getDrupal() {
    return $this->drupal;
  }

  /**
   * Set event dispatcher.
   */
  public function setDispatcher(EventDispatcher $dispatcher) {
    $this->dispatcher = $dispatcher;
  }

  /**
   * Set parameters provided for Drupal.
   */
  public function setDrupalParameters(array $parameters) {
    $this->drupalParameters = $parameters;
  }

  /**
   * Returns a specific Drupal parameter.
   *
   * @param string $name
   *   Parameter name.
   *
   * @return mixed
   */
  public function getDrupalParameter($name) {
    return isset($this->drupalParameters[$name]) ? $this->drupalParameters[$name] : NULL;
  }

  /**
   * Returns a specific Drupal text value.
   *
   * @param string $name
   *   Text value name, such as 'log_out', which corresponds to the default 'Log
   *   out' link text.
   * @throws \Exception
   * @return
   */
  public function getDrupalText($name) {
    $text = $this->getDrupalParameter('text');
    if (!isset($text[$name])) {
      throw new \Exception(sprintf('No such Drupal string: %s', $name));
    }
    return $text[$name];
  }

  /**
   * Get active Drupal Driver.
   */
  public function getDriver($name = NULL) {
    return $this->getDrupal()->getDriver($name);
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
   * @return \Behat\Mink\Element\NodeElement|NULL
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
   * Run before every scenario.
   *
   * @BeforeScenario
   */
  public function beforeScenario($event) {
    if (isset($this->basic_auth)) {
      $driver = $this->getSession()->getDriver();
      if ($driver instanceof Selenium2Driver) {
        // Continue if this is a Selenium driver, since this is handled in
        // locatePath().
      }
      else {
        // Setup basic auth.
        $this->getSession()->setBasicAuth($this->basic_auth['username'], $this->basic_auth['password']);
      }
    }
  }

  /**
   * Run after every scenario.
   *
   * @AfterScenario
   */
  public function afterScenario($event) {
    // Remove any nodes that were created.
    if (!empty($this->nodes)) {
      foreach ($this->nodes as $node) {
        $this->getDriver()->nodeDelete($node);
      }
    }

    // Remove any users that were created.
    if (!empty($this->users)) {
      foreach ($this->users as $user) {
        $this->getDriver()->userDelete($user);
      }
      $this->getDriver()->processBatch();
    }

    // Remove any terms that were created.
    if (!empty($this->terms)) {
      foreach ($this->terms as $term) {
        $this->getDriver()->termDelete($term);
      }
    }

    // Remove any roles that were created.
    if (!empty($this->roles)) {
      foreach ($this->roles as $rid) {
        $this->getDriver()->roleDelete($rid);
      }
    }
  }

  /**
   * Override MinkContext::locatePath() to work around Selenium not supporting
   * basic auth.
   */
  public function locatePath($path) {
    $driver = $this->getSession()->getDriver();
    if ($driver instanceof Selenium2Driver && isset($this->basic_auth)) {
      // Add the basic auth parameters to the base url. This only works for
      // Firefox.
      $startUrl = rtrim($this->getMinkParameter('base_url'), '/') . '/';
      $startUrl = str_replace('://', '://' . $this->basic_auth['username'] . ':' . $this->basic_auth['password'] . '@', $startUrl);
      return 0 !== strpos($path, 'http') ? $startUrl . ltrim($path, '/') : $path;
    }
    else {
      return parent::locatePath($path);
    }
  }

  /**
   * Return the most recent drush command output.
   *
   * @return string
   */
  public function readDrushOutput() {
    if (!isset($this->drushOutput)) {
      throw new pendingException('This scenario has no drush command.');
    }
    return $this->drushOutput;
  }

  /**
   * @defgroup helper functions
   * @{
   */

  /**
   * Helper function to login the current user.
   */
  public function login() {
    // Check if logged in.
    if ($this->loggedIn()) {
      $this->logout();
    }

    if (!$this->user) {
      throw new \Exception('Tried to login without a user.');
    }

    $this->getSession()->visit($this->locatePath('/user'));
    $element = $this->getSession()->getPage();
    $element->fillField($this->getDrupalText('username_field'), $this->user->name);
    $element->fillField($this->getDrupalText('password_field'), $this->user->pass);
    $submit = $element->findButton($this->getDrupalText('log_in'));
    if (empty($submit)) {
      throw new \Exception(sprintf("No submit button at %s", $this->getSession()->getCurrentUrl()));
    }

    // Log in.
    $submit->click();

    if (!$this->loggedIn()) {
      throw new \Exception(sprintf("Failed to log in as user '%s' with role '%s'", $this->user->name, $this->user->role));
    }
  }

  /**
   * Helper function to logout.
   */
  public function logout() {
    $this->getSession()->visit($this->locatePath('/user/logout'));
  }

  /**
   * Determine if the a user is already logged in.
   */
  public function loggedIn() {
    $session = $this->getSession();
    $page = $session->getPage();
    // Look for a css selector to determine if a user is logged in.
    // Default is the logged-in class on the body tag.
    // Which should work with almost any theme.
    try {
      if ($page->has('css', $this->getDrupalSelector('logged_in_selector'))) {
        return TRUE;
      }
    } catch (Exception $e) {
      // This test may fail if the driver did not load any site yet.
    }
    // Some themes do not add that class to the body, so lets check if the
    // login form is displayed on /user/login.
    $session->visit($this->locatePath('/user/login'));
    if (!$page->has('css', $this->getDrupalSelector('login_form_selector'))) {
      return TRUE;
    }
    $session->visit($this->locatePath('/'));
    // As a last resort, if a logout link is found, we are logged in. While not
    // perfect, this is how Drupal SimpleTests currently work as well.
    return $page->findLink($this->getDrupalText('log_out'));
  }

  /**
   * @} End of defgroup "helper functions".
   */

  /**
   * @defgroup mink extensions
   * @{
   * Wrapper step definitions to the Mink extensions in order to implement
   * alternate wording for tests.
   */

  /**
   * Visit a given path, and additionally check for HTTP response code 200.
   *
   * @Given /^(?:that I|I) am at "(?P<path>[^"]*)"$/
   *
   * @throws UnsupportedDriverActionException
   */
  public function assertAtPath($path) {
    $this->getSession()->visit($this->locatePath($path));

    // If available, add extra validation that this is a 200 response.
    try {
      $this->getSession()->getStatusCode();
      return new Given('I should get a "200" HTTP response');
    }
    catch (UnsupportedDriverActionException $e) {
      // Simply continue on, as this driver doesn't support HTTP response codes.
    }
  }

  /**
   * @When /^I visit "(?P<path>[^"]*)"$/
   */
  public function assertVisit($path) {
    // Use Drupal Context 'I am at'.
    return new Given("I am at \"$path\"");
  }

  /**
   * @When /^I click "(?P<link>[^"]*)"$/
   */
  public function assertClick($link) {
    // Use the Mink Extenstion step definition.
    return new Given("I follow \"$link\"");
  }

  /**
   * @Given /^for "(?P<field>[^"]*)" I enter "(?P<value>[^"]*)"$/
   * @Given /^I enter "(?P<value>[^"]*)" for "(?P<field>[^"]*)"$/
   */
  public function assertEnterField($field, $value) {
    // Use the Mink Extenstion step definition.
    return new Given("I fill in \"$field\" with \"$value\"");
  }

  /**
   * For javascript enabled scenarios, always wait for AJAX before clicking.
   *
   * @BeforeStep @javascript
   */
  public function beforeJavascriptStep($event) {
    $text = $event->getStep()->getText();
    if (preg_match('/(follow|press|click|submit)/i', $text)) {
      $this->iWaitForAjaxToFinish();
    }
  }

  /**
   * For javascript enabled scenarios, always wait for AJAX after clicking.
   *
   * @AfterStep @javascript
   */
  public function afterJavascriptStep($event) {
    $text = $event->getStep()->getText();
    if (preg_match('/(follow|press|click|submit)/i', $text)) {
      $this->iWaitForAjaxToFinish();
    }
  }

  /**
   * Wait for AJAX to finish.
   *
   * @Given /^I wait for AJAX to finish$/
   */
  public function iWaitForAjaxToFinish() {
    $this->getSession()->wait(5000, '(typeof(jQuery)=="undefined" || (0 === jQuery.active && 0 === jQuery(\':animated\').length))');
  }

  /**
   * Presses button with specified id|name|title|alt|value.
   *
   * @When /^(?:|I )press the "(?P<button>[^"]*)" button$/
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
   * @Given /^(?:|I )press the "([^"]*)" key in the "([^"]*)" field$/
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
      } else if (strlen($char) > 1) {
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
   * @Then /^I should see the link "(?P<link>[^"]*)"$/
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
   * @Then /^I should not see the link "(?P<link>[^"]*)"$/
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
   * @Then /^I (?:|should )see the heading "(?P<heading>[^"]*)"$/
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
   * @Then /^I (?:|should )not see the heading "(?P<heading>[^"]*)"$/
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
   * Find a heading in a specific region.
   *
   * @Then /^I should see the heading "(?P<heading>[^"]*)" in the "(?P<region>[^"]*)"(?:| region)$/
   * @Then /^I should see the "(?P<heading>[^"]*)" heading in the "(?P<region>[^"]*)"(?:| region)$/
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
   * @When /^I (?:follow|click) "(?P<link>[^"]*)" in the "(?P<region>[^"]*)"(?:| region)$/
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
   * @Then /^I should see the link "(?P<link>[^"]*)" in the "(?P<region>[^"]*)"(?:| region)$/
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
   * @Then /^I should not see the link "(?P<link>[^"]*)" in the "(?P<region>[^"]*)"(?:| region)$/
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
   * @Then /^I should see (?:the text |)"(?P<text>[^"]*)" in the "(?P<region>[^"]*)"(?:| region)$/
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
   * @Then /^I should not see (?:the text |)"(?P<text>[^"]*)" in the "(?P<region>[^"]*)"(?:| region)$/
   *
   * @throws \Exception
   *   If region or text within it cannot be found.
   */
  public function assertNotRegionText($text, $region) {
    $regionObj = $this->getRegion($region);

    // Find the text within the region
    $regionText = $regionObj->getText();
    if (strpos($regionText, $text) !== FALSE) {
      throw new \Exception(sprintf('The text "%s" was found in the region "%s" on the page %s', $text, $region, $this->getSession()->getCurrentUrl()));
    }
  }

  /**
   * Checks, if a button with id|name|title|alt|value exists or not and pressess the same
   *
   * @Given /^I press "(?P<button>[^"]*)" in the "(?P<region>[^"]*)"(?:| region)$/
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
   * @Given /^(?:|I )fill in "(?P<value>(?:[^"]|\\")*)" for "(?P<field>(?:[^"]|\\")*)" in the "(?P<region>[^"]*)"(?:| region)$/
   * @Given /^(?:|I )fill in "(?P<field>(?:[^"]|\\")*)" with "(?P<value>(?:[^"]|\\")*)" in the "(?P<region>[^"]*)"(?:| region)$/
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
   * @Then /^(?:I|I should) see the text "(?P<text>[^"]*)"$/
   */
  public function assertTextVisible($text) {
    // Use the Mink Extension step definition.
    return new Given("I should see text matching \"$text\"");
  }

  /**
   * @Then /^I should not see the text "(?P<text>[^"]*)"$/
   */
  public function assertNotTextVisible($text) {
    // Use the Mink Extension step definition.
    return new Given("I should not see text matching \"$text\"");
  }

  /**
   * @Then /^I should get a "(?P<code>[^"]*)" HTTP response$/
   */
  public function assertHttpResponse($code) {
    // Use the Mink Extension step definition.
    return new Given("the response status code should be $code");
  }

  /**
   * @Then /^I should not get a "(?P<code>[^"]*)" HTTP response$/
   */
  public function assertNotHttpResponse($code) {
    // Use the Mink Extension step definition.
    return new Given("the response status code should not be $code");
  }

  /**
   * @Given /^I check the box "(?P<checkbox>[^"]*)"$/
   */
  public function assertCheckBox($checkbox) {
    // Use the Mink Extension step definition.
    return new Given("I check \"$checkbox\"");
  }

  /**
   * @Given /^I uncheck the box "(?P<checkbox>[^"]*)"$/
   */
  public function assertUncheckBox($checkbox) {
    // Use the Mink Extension step definition.
    return new Given("I uncheck \"$checkbox\"");
  }

  /**
   * @When /^I select the radio button "(?P<label>[^"]*)" with the id "(?P<id>[^"]*)"$/
   * @When /^I select the radio button "(?P<label>[^"]*)"$/
   * @TODO convert to mink extension.
   */
  public function assertSelectRadioById($label, $id = FALSE) {
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

  /**
   * @defgroup drupal extensions
   * @{
   * Drupal-specific step definitions.
   */

  /**
   * @Given /^I am an anonymous user$/
   * @Given /^I am not logged in$/
   */
  public function assertAnonymousUser() {
    // Verify the user is logged out.
    if ($this->loggedIn()) {
      $this->logout();
    }
  }

  /**
   * Creates and authenticates a user with the given role(s) via Drush.
   *
   * @Given /^I am logged in as a user with the "(?P<roles>[^"]*)" role(?:|s)$/
   */
  public function assertAuthenticatedByRole($roles) {
    // Check if a user with this role is already logged in.
    if ($this->loggedIn() && $this->user && isset($this->user->role) && $this->user->role == $roles) {
      return TRUE;
    }

    // Create user (and project)
    $user = (object) array(
      'name' => $this->getDrupal()->random->name(8),
      'pass' => $this->getDrupal()->random->name(16),
      'role' => $roles,
    );
    $user->mail = "{$user->name}@example.com";

    // Create a new user.
    $this->getDriver()->userCreate($user);

    $this->users[$user->name] = $this->user = $user;

    $roles = explode(',', $roles);
    $roles = array_map('trim', $roles);

    foreach ($roles as $role) {
      if (!in_array(strtolower($role), array('authenticated', 'authenticated user'))) {
        // Only add roles other than 'authenticated user'.
        $this->getDriver()->userAddRole($user, $role);
      }
    }

    // Login.
    $this->login();

    return TRUE;
  }

  /**
   * @Given /^I am logged in as "(?P<name>[^"]*)"$/
   */
  public function assertLoggedInByName($name) {
    if (!isset($this->users[$name])) {
      throw new \Exception(sprintf('No user with %s name is registered with the driver.', $name));
    }

    // Change internal current user.
    $this->user = $this->users[$name];

    // Login.
    $this->login();
  }

  /**
   * @Given /^I am logged in as a user with the "(?P<permission>[^"]*)" permission(?:|s)$/
   */
  public function assertLoggedInWithPermissions($permissions) {
    $permissions = explode(',', $permissions);

    $rid = $this->getDriver()->roleCreate($permissions);
    if (!$rid) {
      return FALSE;
    }
    // Create user.
    $user = (object) array(
      'name' => $this->getDrupal()->random->name(8),
      'pass' => $this->getDrupal()->random->name(16),
      'roles' => array($rid),
    );
    $user->mail = "{$user->name}@example.com";

    // Create a new user.
    $this->getDriver()->userCreate($user);

    $this->users[] = $this->user = $user;
    $this->roles[] = $rid;

    // Login.
    $this->login();

    return TRUE;
  }

  /**
   * Retrieve a table row containing specified text from a given element.
   *
   * @param \Behat\Mink\Element\Element
   * @param string
   *   The text to search for in the table row.
   *
   * @return \Behat\Mink\Element\NodeElement
   *
   * @throws \Exception
   */
  public function getTableRow(Element $element, $search) {
    $rows = $element->findAll('css', 'tr');
    if (!$rows) {
      throw new \Exception(sprintf('No rows found on the page %s', $this->getSession()->getCurrentUrl()));
    }
    foreach ($rows as $row) {
      if (strpos($row->getText(), $search) !== FALSE) {
        return $row;
      }
    }
    throw new \Exception(sprintf('Failed to find a row containing "%s" on the page %s', $search, $this->getSession()->getCurrentUrl()));
  }

  /**
   * Find text in a table row containing given text.
   *
   * @Then /^I should see the text "(?P<text>[^"]*)" in the "(?P<row_text>[^"]*)" row$/
   */
  public function assertTextInTableRow($text, $row_text) {
    $row = $this->getTableRow($this->getSession()->getPage(), $row_text);
    if (strpos($row->getText(), $text) === FALSE) {
      throw new \Exception(sprintf('Found a row containing "%s", but it did not contain the text "%s".', $row_text, $text));
    }
  }

  /**
   * Attempts to find a link in a table row containing giving text. This is for
   * administrative pages such as the administer content types screen found at
   * `admin/structure/types`.
   *
   * @Given /^I click "(?P<link>[^"]*)" in the "(?P<row_text>[^"]*)" row$/
   */
  public function assertClickInTableRow($link, $row_text) {
    $page = $this->getSession()->getPage();
    if ($link = $this->getTableRow($page, $row_text)->findLink($link)) {
      // Click the link and return.
      $link->click();
      return;
    }
    throw new \Exception(sprintf('Found a row containing "%s", but no "%s" link on the page %s', $rowText, $link, $this->getSession()->getCurrentUrl()));
  }

  /**
   * @Given /^the cache has been cleared$/
   */
  public function assertCacheClear() {
    $this->getDriver()->clearCache();
  }

  /**
   * @Given /^I run cron$/
   */
  public function assertCron() {
    $this->getDriver()->runCron();
  }

  /**
   * @Given /^I am viewing (?:a|an) "(?P<type>[^"]*)" node with the title "(?P<title>[^"]*)"$/
   * @Given /^(?:a|an) "(?P<type>[^"]*)" node with the title "(?P<title>[^"]*)"$/
   */
  public function createNode($type, $title) {
    // @todo make this easily extensible.
    $node = (object) array(
      'title' => $title,
      'type' => $type,
      'body' => $this->getDrupal()->random->string(255),
    );
    $this->dispatcher->dispatch('beforeNodeCreate', new EntityEvent($this, $node));
    $saved = $this->getDriver()->createNode($node);
    $this->dispatcher->dispatch('afterNodeCreate', new EntityEvent($this, $saved));
    $this->nodes[] = $saved;

    // Set internal page on the new node.
    $this->getSession()->visit($this->locatePath('/node/' . $saved->nid));
  }

  /**
   * @Given /^I am viewing my "(?P<type>[^"]*)" node with the title "(?P<title>[^"]*)"$/
   */
  public function createMyNode($type, $title) {
    if (!$this->user->uid) {
      throw new \Exception(sprintf('There is no current logged in user to create a node for.'));
    }

    $node = (object) array(
      'title' => $title,
      'type' => $type,
      'body' => $this->getDrupal()->random->string(255),
      'uid' => $this->user->uid,
    );
    $this->dispatcher->dispatch('beforeNodeCreate', new EntityEvent($this, $node));
    $saved = $this->getDriver()->createNode($node);
    $this->dispatcher->dispatch('afterNodeCreate', new EntityEvent($this, $saved));
    $this->nodes[] = $saved;

    // Set internal page on the new node.
    $this->getSession()->visit($this->locatePath('/node/' . $saved->nid));
  }

  /**
   * @Given /^"(?P<type>[^"]*)" nodes:$/
   */
  public function createNodes($type, TableNode $nodesTable) {
    foreach ($nodesTable->getHash() as $nodeHash) {
      $node = (object) $nodeHash;
      $node->type = $type;
      $this->dispatcher->dispatch('beforeNodeCreate', new EntityEvent($this, $node));
      $saved = $this->getDriver()->createNode($node);
      $this->dispatcher->dispatch('afterNodeCreate', new EntityEvent($this, $saved));
      $this->nodes[] = $saved;
    }
  }

  /**
   * @Given /^I am viewing (?:a|an) "(?P<type>[^"]*)" node:$/
   */
  public function assertViewingNode($type, TableNode $fields) {
    $node = (object) array(
      'type' => $type,
    );
    foreach ($fields->getRowsHash() as $field => $value) {
      $node->{$field} = $value;
    }

    $this->dispatcher->dispatch('beforeNodeCreate', new EntityEvent($this, $node));
    $saved = $this->getDriver()->createNode($node);
    $this->dispatcher->dispatch('afterNodeCreate', new EntityEvent($this, $saved));
    $this->nodes[] = $saved;

    // Set internal browser on the node.
    $this->getSession()->visit($this->locatePath('/node/' . $saved->nid));
  }

  /**
   * Asserts that a given node type is editable.
   *
   * @Then /^I should be able to edit (?:a|an) "([^"]*)" node$/
   */
  public function assertEditNodeOfType($type) {
    $node = (object) array('type' => $type);
    $saved = $this->getDriver()->createNode($node);
    $this->nodes[] = $saved;

    // Set internal browser on the node edit page.
    $this->getSession()->visit($this->locatePath('/node/' . $saved->nid . '/edit'));

    // Test status.
    return new Then("I should get a \"200\" HTTP response");

  }


  /**
   * @Given /^I am viewing (?:a|an) "(?P<vocabulary>[^"]*)" term with the name "(?P<name>[^"]*)"$/
   * @Given /^(?:a|an) "(?P<vocabulary>[^"]*)" term with the name "(?P<name>[^"]*)"$/
   */
  public function createTerm($vocabulary, $name) {
    // @todo make this easily extensible.
    $term = (object) array(
      'name' => $name,
      'vocabulary_machine_name' => $vocabulary,
      'description' => $this->getDrupal()->random->string(255),
    );
    $this->dispatcher->dispatch('beforeTermCreate', new EntityEvent($this, $term));
    $saved = $this->getDriver()->createTerm($term);
    $this->dispatcher->dispatch('afterTermCreate', new EntityEvent($this, $term));
    $this->terms[] = $saved;

    // Set internal page on the term.
    $this->getSession()->visit($this->locatePath('/taxonomy/term/' . $saved->tid));
  }

  /**
   * Creates multiple users.
   *
   * Provide user data in the following format:
   *
   * | name     | mail         | roles        |
   * | user foo | foo@bar.com  | role1, role2 |
   *
   * @Given /^users:$/
   */
  public function createUsers(TableNode $usersTable) {
    foreach ($usersTable->getHash() as $userHash) {

      // Split out roles to process after user is created.
      $roles = array();
      if (isset($userHash['roles'])) {
        $roles = explode(',', $userHash['roles']);
        $roles = array_map('trim', $roles);
        unset($userHash['roles']);
      }

      $user = (object) $userHash;
      // Set a password.
      if (!isset($user->pass)) {
        $user->pass = $this->getDrupal()->random->name();
      }
      $this->dispatcher->dispatch('beforeUserCreate', new EntityEvent($this, $user));
      $this->getDriver()->userCreate($user);
      $this->dispatcher->dispatch('afterUserCreate', new EntityEvent($this, $user));
      $this->users[$user->name] = $user;

      // Assign roles.
      foreach ($roles as $role) {
        $this->getDriver()->userAddRole($user, $role);
      }
    }
  }

  /**
   * @Given /^"(?P<vocabulary>[^"]*)" terms:$/
   */
  public function createTerms($vocabulary, TableNode $termsTable) {
    foreach ($termsTable->getHash() as $termsHash) {
      $term = (object) $termsHash;
      $term->vocabulary_machine_name = $vocabulary;
      $this->dispatcher->dispatch('beforeTermCreate', new EntityEvent($this, $term));
      $saved = $this->getDriver()->createTerm($term);
      $this->dispatcher->dispatch('afterTermCreate', new EntityEvent($this, $term));
      $this->terms[] = $saved;
    }
  }

  /**
   * Checks if the current page contains the given error message
   *
   * @param $message
   *   string The text to be checked
   *
   * @Then /^I should see the error message(?:| containing) "([^"]*)"$/
   */
  public function assertErrorVisible($message) {
    $errorSelector = $this->getDrupalSelector('error_message_selector');
    $errorSelectorObj = $this->getSession()->getPage()->find("css", $errorSelector);
    if(empty($errorSelectorObj)) {
      throw new \Exception(sprintf("The page '%s' does not contain any error messages", $this->getSession()->getCurrentUrl()));
    }
    if (strpos(trim($errorSelectorObj->getText()), $message) === FALSE) {
      throw new \Exception(sprintf("The page '%s' does not contain the error message '%s'", $this->getSession()->getCurrentUrl(), $message));
    }
  }

  /**
   * Checks if the current page contains the given set of error messages
   *
   * @param $messages
   *   array An array of texts to be checked
   *
   * @Then /^I should see the following <error messages>$/
   */
  public function assertMultipleErrors(TableNode $messages) {
    $steps = array();
    foreach ($messages->getHash() as $key => $value) {
      $message = trim($value['error messages']);
      $steps[] = new Then("I should see the error message \"$message\"");
    }
    return $steps;
  }

  /**
   * Checks if the current page does not contain the given error message
   *
   * @param $message
   *   string The text to be checked
   *
   * @Given /^I should not see the error message(?:| containing) "([^"]*)"$/
   */
  public function assertNotErrorVisible($message) {
    $errorSelector = $this->getDrupalSelector('error_message_selector');
    $errorSelectorObj = $this->getSession()->getPage()->find("css", $errorSelector);
    if(!empty($errorSelectorObj)) {
      if (strpos(trim($errorSelectorObj->getText()), $message) !== FALSE) {
        throw new \Exception(sprintf("The page '%s' contains the error message '%s'", $this->getSession()->getCurrentUrl(), $message));
      }
    }
  }

  /**
   * Checks if the current page does not contain the given set error messages
   *
   * @param $messages
   *   array An array of texts to be checked
   *
   * @Then /^I should not see the following <error messages>$/
   */
  public function assertNotMultipleErrors(TableNode $messages) {
    $steps = array();
    foreach ($messages->getHash() as $key => $value) {
      $message = trim($value['error messages']);
      $steps[] = new Then("I should not see the error message \"$message\"");
    }
    return $steps;
  }

  /**
   * Checks if the current page contains the given success message
   *
   * @param $message
   *   string The text to be checked
   *
   * @Then /^I should see the success message(?:| containing) "([^"]*)"$/
   */
  public function assertSuccessMessage($message) {
    $successSelector = $this->getDrupalSelector('success_message_selector');
    $successSelectorObj = $this->getSession()->getPage()->find("css", $successSelector);
    if(empty($successSelectorObj)) {
      throw new \Exception(sprintf("The page '%s' does not contain any success messages", $this->getSession()->getCurrentUrl()));
    }
    if (strpos(trim($successSelectorObj->getText()), $message) === FALSE) {
      throw new \Exception(sprintf("The page '%s' does not contain the success message '%s'", $this->getSession()->getCurrentUrl(), $message));
    }
  }

  /**
   * Checks if the current page contains the given set of success messages
   *
   * @param $message
   *   array An array of texts to be checked
   *
   * @Then /^I should see the following <success messages>$/
   */
  public function assertMultipleSuccessMessage(TableNode $messages) {
    $steps = array();
    foreach ($messages->getHash() as $key => $value) {
      $message = trim($value['success messages']);
      $steps[] = new Then("I should see the success message \"$message\"");
    }
    return $steps;
  }

  /**
   * Checks if the current page does not contain the given set of success message
   *
   * @param $message
   *   string The text to be checked
   *
   * @Given /^I should not see the success message(?:| containing) "([^"]*)"$/
   */
  public function assertNotSuccessMessage($message) {
    $successSelector = $this->getDrupalSelector('success_message_selector');
    $successSelectorObj = $this->getSession()->getPage()->find("css", $successSelector);
    if(!empty($successSelectorObj)) {
      if (strpos(trim($successSelectorObj->getText()), $message) !== FALSE) {
        throw new \Exception(sprintf("The page '%s' contains the success message '%s'", $this->getSession()->getCurrentUrl(), $message));
      }
    }
  }

  /**
   * Checks if the current page does not contain the given set of success messages
   *
   * @param $message
   *   array An array of texts to be checked
   *
   * @Then /^I should not see the following <success messages>$/
   */
  public function assertNotMultipleSuccessMessage(TableNode $messages) {
    $steps = array();
    foreach ($messages->getHash() as $key => $value) {
      $message = trim($value['success messages']);
      $steps[] = new Then("I should not see the success message \"$message\"");
    }
    return $steps;
  }

  /**
   * Checks if the current page contains the given warning message
   *
   * @param $message
   *   string The text to be checked
   *
   * @Then /^I should see the warning message(?:| containing) "([^"]*)"$/
   */
  public function assertWarningMessage($message) {
    $warningSelector = $this->getDrupalSelector('warning_message_selector');
    $warningSelectorObj = $this->getSession()->getPage()->find("css", $warningSelector);
    if(empty($warningSelectorObj)) {
      throw new \Exception(sprintf("The page '%s' does not contain any warning messages", $this->getSession()->getCurrentUrl()));
    }
    if (strpos(trim($warningSelectorObj->getText()), $message) === FALSE) {
      throw new \Exception(sprintf("The page '%s' does not contain the warning message '%s'", $this->getSession()->getCurrentUrl(), $message));
    }
  }

  /**
   * Checks if the current page contains the given set of warning messages
   *
   * @param $message
   *   array An array of texts to be checked
   *
   * @Then /^I should see the following <warning messages>$/
   */
  public function assertMultipleWarningMessage(TableNode $messages) {
    $steps = array();
    foreach ($messages->getHash() as $key => $value) {
      $message = trim($value['warning messages']);
      $steps[] = new Then("I should see the warning message \"$message\"");
    }
    return $steps;
  }

  /**
   * Checks if the current page does not contain the given set of warning message
   *
   * @param $message
   *   string The text to be checked
   *
   * @Given /^I should not see the warning message(?:| containing) "([^"]*)"$/
   */
  public function assertNotWarningMessage($message) {
    $warningSelector = $this->getDrupalSelector('warning_message_selector');
    $warningSelectorObj = $this->getSession()->getPage()->find("css", $warningSelector);
    if(!empty($warningSelectorObj)) {
      if (strpos(trim($warningSelectorObj->getText()), $message) !== FALSE) {
        throw new \Exception(sprintf("The page '%s' contains the warning message '%s'", $this->getSession()->getCurrentUrl(), $message));
      }
    }
  }

  /**
   * Checks if the current page does not contain the given set of warning messages
   *
   * @param $message
   *   array An array of texts to be checked
   *
   * @Then /^I should not see the following <warning messages>$/
   */
  public function assertNotMultipleWarningMessage(TableNode $messages) {
    $steps = array();
    foreach ($messages->getHash() as $key => $value) {
      $message = trim($value['warning messages']);
      $steps[] = new Then("I should not see the warning message \"$message\"");
    }
    return $steps;
  }

  /**
   * Checks if the current page contain the given message
   *
   * @param $message
   *   string The message to be checked
   *
   * @Then /^I should see the message(?:| containing) "([^"]*)"$/
   */
  public function assertMessage($message) {
    $msgSelector = $this->getDrupalSelector('message_selector');
    $msgSelectorObj = $this->getSession()->getPage()->find("css", $msgSelector);
    if(empty($msgSelectorObj)) {
      throw new \Exception(sprintf("The page '%s' does not contain any messages", $this->getSession()->getCurrentUrl()));
    }
    if (strpos(trim($msgSelectorObj->getText()), $message) === FALSE) {
      throw new \Exception(sprintf("The page '%s' does not contain the message '%s'", $this->getSession()->getCurrentUrl(), $message));
    }
  }

  /**
   * Checks if the current page does not contain the given message
   *
   * @param $message
   *   string The message to be checked
   *
   * @Then /^I should not see the message(?:| containing) "([^"]*)"$/
   */
  public function assertNotMessage($message) {
    $msgSelector = $this->getDrupalSelector('message_selector');
    $msgSelectorObj = $this->getSession()->getPage()->find("css", $msgSelector);
    if(!empty($msgSelectorObj)) {
      if (strpos(trim($msgSelectorObj->getText()), $message) !== FALSE) {
        throw new \Exception(sprintf("The page '%s' contains the message '%s'", $this->getSession()->getCurrentUrl(), $message));
      }
    }
  }

  /**
   * Returns a specific css selector.
   *
   * @param $name
   *   string CSS selector name
   */
  public function getDrupalSelector($name) {
    $text = $this->getDrupalParameter('selectors');
    if (!isset($text[$name])) {
      throw new \Exception(sprintf('No such selector configured: %s', $name));
    }
    return $text[$name];
  }

  /**
   * @} End of defgroup "drupal extensions"
   */
  /**
   * @defgroup drush steps
   * @{
   */

  /**
   * @Given /^I run drush "(?P<command>[^"]*)"$/
   */
  public function assertDrushCommand($command) {
    if (!$this->drushOutput = $this->getDriver('drush')->$command()) {
       $this->drushOutput = TRUE;
    }
  }

  /**
   * @Given /^I run drush "(?P<command>[^"]*)" "(?P<arguments>(?:[^"]|\\")*)"$/
   */
  public function assertDrushCommandWithArgument($command, $arguments) {
    $this->drushOutput = $this->getDriver('drush')->$command($this->fixStepArgument($arguments));
    if (!isset($this->drushOutput)) {
      $this->drushOutput = TRUE;
    }
  }

  /**
   * @Then /^drush output should contain "(?P<output>(?:[^"]|\\")*)"$/
   */
  public function assertDrushOutput($output) {
    if (strpos($this->readDrushOutput(), $this->fixStepArgument($output)) === FALSE) {
      throw new \Exception(sprintf("The last drush command output did not contain '%s'.\nInstead, it was:\n\n%s'", $output, $this->drushOutput));
    }
  }

  /**
   * @Then /^drush output should not contain "(?P<output>(?:[^"]|\\")*)"$/
   */
  public function drushOutputShouldNotContain($output) {
    if (strpos($this->readDrushOutput(), $this->fixStepArgument($output)) !== FALSE) {
        throw new \Exception(sprintf("The last drush command output did contain '%s' although it should not.\nOutput:\n\n%s'", $output, $this->drushOutput));
    }
  }

  /**
   * @Then /^print last drush output$/
   */
  public function printLastDrushOutput() {
    echo $this->readDrushOutput();
  }

  /**
   * @} End of defgroup "drush steps"
   */
  /**
   * @defgroup "debugging steps"
   * @{
   */

  /**
   * Pauses the scenario until the user presses a key. Useful when debugging a scenario.
   *
   * @Then /^(?:|I )break$/
   */
    public function iPutABreakpoint()
    {
      fwrite(STDOUT, "\033[s \033[93m[Breakpoint] Press \033[1;93m[RETURN]\033[0;93m to continue...\033[0m");
      while (fgets(STDIN, 1024) == '') {}
      fwrite(STDOUT, "\033[u");
      return;
    }

  /**
   * @} End of defgroup "debugging steps"
   */
}
