<?php

namespace Drupal\DrupalExtension\Context;

use Behat\MinkExtension\Context\MinkContext;
use Behat\Behat\Tester\Exception\PendingException;
use Behat\Behat\Event\ScenarioEvent;
use Behat\Mink\Exception\UnsupportedDriverActionException;
use Behat\Testwork\Hook\HookDispatcher;

use Drupal\DrupalDriverManager;
use Drupal\DrupalExtension\Context\DrupalSubContextInterface;
use Drupal\DrupalExtension\Hook\Scope\AfterNodeCreateScope;
use Drupal\DrupalExtension\Hook\Scope\AfterTermCreateScope;
use Drupal\DrupalExtension\Hook\Scope\AfterUserCreateScope;
use Drupal\DrupalExtension\Hook\Scope\BeforeNodeCreateScope;
use Drupal\DrupalExtension\Hook\Scope\BeforeUserCreateScope;
use Drupal\DrupalExtension\Hook\Scope\BeforeTermCreateScope;

use Behat\Behat\Context\TranslatableContext;

use Behat\Gherkin\Node\PyStringNode;
use Behat\Gherkin\Node\TableNode;

use Behat\Mink\Driver\Selenium2Driver as Selenium2Driver;

/**
 * Features context.
 *
 * @todo is there a way to inject a MinkContext instead?
 */
class DrupalContext extends MinkContext implements DrupalAwareInterface, TranslatableContext {

  /**
   * Drupal driver manager.
   *
   * @var \Drupal\DrupalDriverManager
   */
  private $drupal;

  /**
   * Test parameters.
   *
   * @var array
   */
  private $drupalParameters;

  /**
   * Event dispatcher object.
   *
   * @var \Behat\Testwork\Hook\HookDispatcher
   */
  protected $dispatcher;

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
   * Returns list of definition translation resources paths.
   *
   * @return array
   */
  public static function getTranslationResources() {
    return glob(__DIR__ . '/../../../../i18n/*.xliff');
  }

  /**
   * Set Drupal instance.
   */
  public function setDrupal(DrupalDriverManager $drupal) {
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
  public function setDispatcher(HookDispatcher $dispatcher) {
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
   * Get driver's random generator.
   */
  public function getRandom() {
    return $this->getDriver()->getRandom();
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
   * Remove any created nodes.
   *
   * @AfterScenario
   */
  public function cleanNodes() {
    // Remove any nodes that were created.
    foreach ($this->nodes as $node) {
      $this->getDriver()->nodeDelete($node);
    }
  }

  /**
   * Remove any created users.
   *
   * @AfterScenario
   */
  public function cleanUsers() {
    // Remove any users that were created.
    if (!empty($this->users)) {
      foreach ($this->users as $user) {
        $this->getDriver()->userDelete($user);
      }
      $this->getDriver()->processBatch();
    }
  }

  /**
   * Remove any created terms.
   *
   * @AfterScenario
   */
  public function cleanTerms() {
    // Remove any terms that were created.
    foreach ($this->terms as $term) {
      $this->getDriver()->termDelete($term);
    }
  }

  /**
   * Remove any created roles.
   *
   * @AfterScenario
   */
  public function cleanRoles () {
    // Remove any roles that were created.
    foreach ($this->roles as $rid) {
      $this->getDriver()->roleDelete($rid);
    }
  }

  /**
   * Helper function to create a node.
   *
   * @return object
   *   The created node.
   */
  public function nodeCreate($node) {
    // @todo this doesn't properly throw exceptions.
    $this->dispatcher->dispatchScopeHooks(new BeforeNodeCreateScope($this->getDrupal()->getEnvironment(), $this, $node));
    $saved = $this->getDriver()->createNode($node);
    $this->dispatcher->dispatchScopeHooks(new AfterNodeCreateScope($this->getDrupal()->getEnvironment(), $this, $saved));
    $this->nodes[] = $saved;
    return $saved;
  }

  /**
   * Helper function to create a user.
   *
   * @return object
   *   The created user.
   */
  public function userCreate($user) {
    // @todo this doesn't properly throw exceptions.
    $this->dispatcher->dispatchScopeHooks(new BeforeUserCreateScope($this->getDrupal()->getEnvironment(), $this, $user));
    $this->getDriver()->userCreate($user);
    $this->dispatcher->dispatchScopeHooks(new AfterUserCreateScope($this->getDrupal()->getEnvironment(), $this, $user));
    $this->users[$user->name] = $this->user = $user;
    return $user;
  }

  /**
   * Helper function to create a term.
   *
   * @return object
   *   The created term.
   */
  public function termCreate($term) {
    // @todo this doesn't properly throw exceptions.
    $this->dispatcher->dispatchScopeHooks(new BeforeTermCreateScope($this->getDrupal()->getEnvironment(), $this, $term));
    $saved = $this->getDriver()->createTerm($term);
    $this->dispatcher->dispatchScopeHooks(new AfterTermCreateScope($this->getDrupal()->getEnvironment(), $this, $saved));
    $this->terms[] = $saved;
    return $saved;
  }

  /**
   * Return the most recent drush command output.
   *
   * @return string
   */
  public function readDrushOutput() {
    if (!isset($this->drushOutput)) {
      throw new PendingException('This scenario has no drush command.');
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
    $session->visit($this->locatePath('/'));

    // If a logout link is found, we are logged in. While not perfect, this is
    // how Drupal SimpleTests currently work as well.
    $element = $session->getPage();
    return $element->findLink($this->getDrupalText('log_out'));
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

  /**
   * @defgroup drupal extensions
   * @{
   * Drupal-specific step definitions.
   */

  /**
   * @Given I am an anonymous user
   * @Given I am not logged in
   */
  public function assertAnonymousUser() {
    // Verify the user is logged out.
    if ($this->loggedIn()) {
      $this->logout();
    }
  }

  /**
   * Creates and authenticates a user with the given role via Drush.
   *
   * @Given I am logged in as a user with the :role role
   */
  public function assertAuthenticatedByRole($role) {
    // Check if a user with this role is already logged in.
    if ($this->loggedIn() && $this->user && isset($this->user->role) && $this->user->role == $role) {
      return TRUE;
    }

    // Create user (and project)
    $user = (object) array(
      'name' => $this->getRandom()->name(8),
      'pass' => $this->getRandom()->name(16),
      'role' => $role,
    );
    $user->mail = "{$user->name}@example.com";

    $this->userCreate($user);

    if ($role == 'authenticated user') {
      // Nothing to do.
    }
    else {
      $this->getDriver()->userAddRole($user, $role);
    }

    // Login.
    $this->login();

    return TRUE;
  }

  /**
   * @Given I am logged in as :name
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
   * @Given I am logged in as a user with the :permissions permission(s)
   */
  public function assertLoggedInWithPermissions($permissions) {
    $permissions = explode(',', $permissions);

    $rid = $this->getDriver()->roleCreate($permissions);
    if (!$rid) {
      throw new \Exception(sprintf('No role with permissions (%s) was created!', implode(', ', $permissions)));
    }

    // Create user.
    $user = (object) array(
      'name' => $this->getRandom()->name(8),
      'pass' => $this->getRandom()->name(16),
      'roles' => array($rid),
    );
    $user->mail = "{$user->name}@example.com";

    $this->userCreate($user);
    $this->roles[] = $rid;

    // Login.
    $this->login();
  }

  /**
   * Attempts to find a link in a table row containing giving text. This is for
   * administrative pages such as the administer content types screen found at
   * `admin/structure/types`.
   *
   * @Given I click :link in the :rowText row
   */
  public function assertClickInTableRow($link, $rowText) {
    $page = $this->getSession()->getPage();
    $rows = $page->findAll('css', 'tr');
    if (!$rows) {
      throw new \Exception(sprintf('No rows found on the page %s', $this->getSession()->getCurrentUrl()));
    }
    $row_found = FALSE;
    foreach ($rows as $row) {
      if (strpos($row->getText(), $rowText) !== FALSE) {
        $row_found = TRUE;
        // Found text in this row, now find link in a cell.
        $cells = $row->findAll('css', 'td');
        if (!$cells) {
          throw new \Exception(sprintf('No cells found in table row on the page %s', $this->getSession()->getCurrentUrl()));
        }
        foreach ($cells as $cell) {
          if ($element = $cell->findLink($link)) {
            $element->click();
            return;
          }
        }
      }
    }
    if ($row_found) {
      throw new \Exception(sprintf('Found a row containing "%s", but no "%s" link on the page %s', $rowText, $link, $this->getSession()->getCurrentUrl()));
    }
    else {
      throw new \Exception(sprintf('Failed to find a row containing "%s" on the page %s', $rowText, $this->getSession()->getCurrentUrl()));
    }
  }

  /**
   * @Given the cache has been cleared
   */
  public function assertCacheClear() {
    $this->getDriver()->clearCache();
  }

  /**
   * @Given I run cron
   */
  public function assertCron() {
    $this->getDriver()->runCron();
  }

  /**
   * @Given I am viewing a/an :type node with the title :title
   * @Given a/an :type node with the title :title
   */
  public function createNode($type, $title) {
    // @todo make this easily extensible.
    $node = (object) array(
      'title' => $title,
      'type' => $type,
      'body' => $this->getRandom()->string(255),
    );
    $saved = $this->nodeCreate($node);
    // Set internal page on the new node.
    $this->getSession()->visit($this->locatePath('/node/' . $saved->nid));
  }

  /**
   * @Given I am viewing my :type node with the title :title
   */
  public function createMyNode($type, $title) {
    if (!$this->user->uid) {
      throw new \Exception(sprintf('There is no current logged in user to create a node for.'));
    }

    $node = (object) array(
      'title' => $title,
      'type' => $type,
      'body' => $this->getRandom()->string(255),
      'uid' => $this->user->uid,
    );
    $saved = $this->nodeCreate($node);

    // Set internal page on the new node.
    $this->getSession()->visit($this->locatePath('/node/' . $saved->nid));
  }

  /**
   * @Given :type nodes:
   */
  public function createNodes($type, TableNode $nodesTable) {
    foreach ($nodesTable->getHash() as $nodeHash) {
      $node = (object) $nodeHash;
      $node->type = $type;
      $this->nodeCreate($node);
    }
  }

  /**
   * @Given I am viewing a/an :type node:
   */
  public function assertViewingNode($type, TableNode $fields) {
    $node = (object) array(
      'type' => $type,
    );
    foreach ($fields->getRowsHash() as $field => $value) {
      $node->{$field} = $value;
    }

    $saved = $this->nodeCreate($node);

    // Set internal browser on the node.
    $this->getSession()->visit($this->locatePath('/node/' . $saved->nid));
  }

  /**
   * Asserts that a given node type is editable.
   *
   * @Then I should be able to edit a/an :type node
   */
  public function assertEditNodeOfType($type) {
    $node = (object) array('type' => $type);
    $saved = $this->nodeCreate($node);

    // Set internal browser on the node edit page.
    $this->getSession()->visit($this->locatePath('/node/' . $saved->nid . '/edit'));

    // Test status.
    $this->assertHttpResponse('200');
  }


  /**
   * @Given I am viewing a/an :vocabulary term with the name :name
   * @Given a/an :vocabulary term with the name :name
   */
  public function createTerm($vocabulary, $name) {
    // @todo make this easily extensible.
    $term = (object) array(
      'name' => $name,
      'vocabulary_machine_name' => $vocabulary,
      'description' => $this->getRandom()->string(255),
    );
    $saved = $this->termCreate($term);

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
   * @Given users:
   */
  public function createUsers(TableNode $usersTable) {
    foreach ($usersTable->getHash() as $userHash) {

      // If we have roles convert it to array and convert role names to rids.
      if (isset($userHash['roles'])) {
        $userHash['roles'] = explode(',', $userHash['roles']);
        $userHash['roles'] = array_map('trim', $userHash['roles']);

        // Role names to rid.
        foreach ($userHash['roles'] as &$input_role)  {
          $role = user_role_load_by_name($input_role);
          if ($role) {
            $input_role = $role->rid;
          }
          else {
            throw new \Exception(sprintf('No such role: %s', $input_role));
          }
        }
      }

      $user = (object) $userHash;

      // Set a password.
      if (!isset($user->pass)) {
        $user->pass = $this->getRandom()->name();
      }

      $this->userCreate($user);
    }
  }

  /**
   * @Given :vocabulary terms:
   */
  public function createTerms($vocabulary, TableNode $termsTable) {
    foreach ($termsTable->getHash() as $termsHash) {
      $term = (object) $termsHash;
      $term->vocabulary_machine_name = $vocabulary;
      $this->termCreate($term);
    }
  }

  /**
   * Checks if the current page contains the given error message
   *
   * @param $message
   *   string The text to be checked
   *
   * @Then I should see the error message( containing) :message
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
   * @Then I should see the following error message(s):
   */
  public function assertMultipleErrors(TableNode $messages) {
    foreach ($messages->getHash() as $key => $value) {
      $message = trim($value['error messages']);
      $this->assertErrorVisible($message);
    }
  }

  /**
   * Checks if the current page does not contain the given error message
   *
   * @param $message
   *   string The text to be checked
   *
   * @Given I should not see the error message( containing) :message
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
   * @Then I should not see the following error messages:
   */
  public function assertNotMultipleErrors(TableNode $messages) {
    foreach ($messages->getHash() as $key => $value) {
      $message = trim($value['error messages']);
      $this->assertNotErrorVisible($message);
    }
  }

  /**
   * Checks if the current page contains the given success message
   *
   * @param $message
   *   string The text to be checked
   *
   * @Then I should see the success message( containing) :message
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
   * @Then I should see the following success messages:
   */
  public function assertMultipleSuccessMessage(TableNode $messages) {
    foreach ($messages->getHash() as $key => $value) {
      $message = trim($value['success messages']);
      $this->assertSuccessMessage($message);
    }
  }

  /**
   * Checks if the current page does not contain the given set of success message
   *
   * @param $message
   *   string The text to be checked
   *
   * @Given I should not see the success message( containing) :message
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
   * @Then I should not see the following success messages:
   */
  public function assertNotMultipleSuccessMessage(TableNode $messages) {
    foreach ($messages->getHash() as $key => $value) {
      $message = trim($value['success messages']);
      $this->assertNotSuccessMessage($message);
    }
  }

  /**
   * Checks if the current page contains the given warning message
   *
   * @param $message
   *   string The text to be checked
   *
   * @Then I should see the warning message( containing) :message
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
   * @Then I should see the following warning messages:
   */
  public function assertMultipleWarningMessage(TableNode $messages) {
    foreach ($messages->getHash() as $key => $value) {
      $message = trim($value['warning messages']);
      $this->assertWarningMessage($message);
    }
  }

  /**
   * Checks if the current page does not contain the given set of warning message
   *
   * @param $message
   *   string The text to be checked
   *
   * @Given I should not see the warning message( containing) :message
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
   * @Then I should not see the following warning messages:
   */
  public function assertNotMultipleWarningMessage(TableNode $messages) {
    foreach ($messages->getHash() as $key => $value) {
      $message = trim($value['warning messages']);
      $this->assertNotWarningMessage($message);
    }
  }

  /**
   * Checks if the current page contain the given message
   *
   * @param $message
   *   string The message to be checked
   *
   * @Then I should see the message( containing) :message
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
   * @Then I should not see the message( containing) :message
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
   * @Given I run drush :command
   */
  public function assertDrushCommand($command) {
    if (!$this->drushOutput = $this->getDriver('drush')->$command()) {
       $this->drushOutput = TRUE;
    }
  }

  /**
   * @Given I run drush :command :arguments
   */
  public function assertDrushCommandWithArgument($command, $arguments) {
    $this->drushOutput = $this->getDriver('drush')->$command($this->fixStepArgument($arguments));
    if (!isset($this->drushOutput)) {
      $this->drushOutput = TRUE;
    }
  }

  /**
   * @Then drush output should contain :output
   */
  public function assertDrushOutput($output) {
    if (strpos($this->readDrushOutput(), $this->fixStepArgument($output)) === FALSE) {
      throw new \Exception(sprintf("The last drush command output did not contain '%s'.\nInstead, it was:\n\n%s'", $output, $this->drushOutput));
    }
  }

  /**
   * @Then drush output should not contain :output
   */
  public function drushOutputShouldNotContain($output) {
    if (strpos($this->readDrushOutput(), $this->fixStepArgument($output)) !== FALSE) {
        throw new \Exception(sprintf("The last drush command output did contain '%s' although it should not.\nOutput:\n\n%s'", $output, $this->drushOutput));
    }
  }

  /**
   * @Then print last drush output
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
   * @Then (I )break
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
