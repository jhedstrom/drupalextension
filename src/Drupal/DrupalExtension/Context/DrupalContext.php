<?php

namespace Drupal\DrupalExtension\Context;

use Behat\MinkExtension\Context\MinkContext;
use Behat\Behat\Exception\PendingException;
use Behat\Mink\Exception\UnsupportedDriverActionException;

use Drupal\Drupal;
use Drupal\DrupalExtension\Context\DrupalSubContextInterface;

use Symfony\Component\Process\Process;

use Behat\Behat\Context\Step\Given;
use Behat\Behat\Context\Step\When;
use Behat\Behat\Context\Step\Then;

use Behat\Mink\Driver\Selenium2Driver as Selenium2Driver;

/**
 * Features context.
 */
class DrupalContext extends MinkContext implements DrupalAwareInterface {

  private $drupal, $drupalParameters;

  /**
   * Basic auth user and password.
   */
  public $basic_auth;

  /**
   * Current authenticated user.
   *
   * A value of FALSE denotes an anonymous user.
   */
  public $user = FALSE;

  /**
   * Keep track of all users that are created so they can easily be removed.
   */
  private $users = array();

  /**
   * Initialize subcontexts.
   *
   * @param array $subcontexts
   *   Array of sub-context class names to initiate, keyed by sub-context alias.
   */
  public function initializeSubContexts(array $subcontexts) {
    foreach ($subcontexts as $path => $subcontext) {
      // Load file.
      require_once $path;
    }

    // @todo this seems overkill.
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
   * Check for shell access (via drush).
   *
   * @BeforeScenario @shellAccess
   */
  public function checkShellAccess() {
    // @todo fix/move
    return;
    // @todo check that this is a functioning alias.
    // See http://drupal.org/node/1615450
    if (!$this->drushAlias) {
      throw new pendingException('This scenario requires shell access.');
    }
  }

  /**
   * Run after every scenario.
   *
   * @AfterScenario
   */
  public function afterScenario($event) {
    // Remove any users that were created.
    if (!empty($this->users)) {
      foreach ($this->users as $user) {
        $this->getDriver()->userDelete($user);
      }
    }
  }

  /**
   * Override MinkContext::locatePath() to work around Selenium not supporting
   * basic auth.
   */
  protected function locatePath($path) {
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
   * @defgroup helper functions
   * @{
   */

  /**
   * Helper function to generate a random string of arbitrary length.
   *
   * Copied from drush_generate_password().
   *
   * @param int $length
   *   Number of characters the generated string should contain.
   *
   * @return string
   *   The generated string.
   */
  public function randomString($length = 10) {
    // This variable contains the list of allowable characters for the
    // password. Note that the number 0 and the letter 'O' have been
    // removed to avoid confusion between the two. The same is true
    // of 'I', 1, and 'l'.
    $allowable_characters = 'abcdefghijkmnopqrstuvwxyzABCDEFGHJKLMNPQRSTUVWXYZ23456789';

    // Zero-based count of characters in the allowable list:
    $len = strlen($allowable_characters) - 1;

    // Declare the password as a blank string.
    $pass = '';

    // Loop the number of times specified by $length.
    for ($i = 0; $i < $length; $i++) {

      // Each iteration, pick a random character from the
      // allowable string and append it to the password:
      $pass .= $allowable_characters[mt_rand(0, $len)];
    }

    return $pass;
  }

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
      throw new \Exception('No submit button at ' . $this->getSession()->getCurrentUrl());
    }

    // Log in.
    $submit->click();

    if (!$this->loggedIn()) {
      throw new \Exception("Failed to log in as user \"{$this->user->name}\" with role \"{$this->user->role}\".");
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
   * @Given /^(?:that I|I) am at "(?P<path>[^"]*)"$/
   *
   * @throws UnsupportedDriverActionException
   */
  public function iAmAt($path) {
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
  public function iVisit($path) {
    // Use Drupal Context 'I am at'.
    return new Given("I am at \"$path\"");
  }

  /**
   * @Given /^(?:that I|I) am (?:on|at) the homepage$/
   */
  public function thatIAmOnTheHomepage() {
    $path = $this->locatePath('/');
    // Use Drupal Context 'I am at'.
    return new Given("I am at \"$path\"");
  }

  /**
   * @When /^I click "(?P<link>[^"]*)"$/
   */
  public function iClick($link) {
    // Use the Mink Extenstion step definition.
    return new Given("I follow \"$link\"");
  }

  /**
   * @Given /^for "(?P<field>[^"]*)" I enter "(?P<value>[^"]*)"$/
   * @Given /^I enter "(?P<value>[^"]*)" for "(?P<field>[^"]*)"$/
   */
  public function forIenter($field, $value) {
    // Use the Mink Extenstion step definition.
    return new Given("I fill in \"$field\" with \"$value\"");
  }

  /**
   * @When /^I press the "(?P<button>[^"]*)" button$/
   */
  public function iPressTheButton($button) {
    // Use the Mink Extenstion step definition.
    return new Given("I press \"$button\"");
  }

  /**
   * @Then /^I should see the link "(?P<link>[^"]*)"$/
   */
  public function iShouldSeeTheLink($link) {
    $element = $this->getSession()->getPage();
    $result = $element->findLink($link);
    if (empty($result)) {
      throw new \Exception("No link to " . $link . " on " . $this->getSession()->getCurrentUrl());
    }
  }

  /**
   * @Then /^I should not see the link "(?P<link>[^"]*)"$/
   */
  public function iShouldNotSeeTheLink($link) {
    $element = $this->getSession()->getPage();
    $result = $element->findLink($link);
    if ($result) {
      throw new \Exception("The link " . $link . " was present on " . $this->getSession()->getCurrentUrl() . " and was not supposed to be.");
    }
  }

  /**
   * @Then /^I (?:|should )see the heading "(?P<heading>[^"]*)"$/
   */
  public function iShouldSeeTheHeading($heading) {
    $element = $this->getSession()->getPage();
    foreach (array('h1', 'h2', 'h3', 'h4', 'h5', 'h6') as $tag) {
      $results = $element->findAll('css', $tag);
      foreach ($results as $result) {
        if ($result->getText() == $heading) {
          return;
        }
      }
    }
    throw new \Exception("The text " . $heading . " was not found in any heading " . $this->getSession()->getCurrentUrl());
  }

  /**
   * Find a heading in a specific region.
   *
   * @Then /^I should see the heading "(?P<heading>[^"]*)" in the "(?P<region>[^"]*)"(?:| region)$/
   */
  public function iShouldSeeTheHeadingInThe($heading, $region) {
    $page = $this->getSession()->getPage();
    $region = $page->find('region', $region);
    if (!$region) {
      throw new Exception("$region region was not found");
    }

    $elements = $region->findAll('css', 'h2');
    $found = FALSE;
    if (!empty($elements)) {
      foreach ($elements as $element) {
        $text = $element->getText();
        if ($text === $heading) {
          $found = TRUE;
          continue;
        }
      }
    }
    if (!$found) {
      throw new Exception("The heading \"$heading\" was not found in the \"$region\" region.");
    }
  }

  /**
   * @Then /^(?:I|I should) see the text "(?P<text>[^"]*)"$/
   */
  public function iShouldSeeTheText($text) {
    // Use the Mink Extension step definition.
    return new Given("I should see text matching \"$text\"");
  }

  /**
   * @Then /^I should not see the text "(?P<text>[^"]*)"$/
   */
  public function iShouldNotSeeTheText($text) {
    // Use the Mink Extension step definition.
    return new Given("I should not see text matching \"$text\"");
  }

  /**
   * @Then /^I should get a "(?P<code>[^"]*)" HTTP response$/
   */
  public function iShouldGetAHttpResponse($code) {
    // Use the Mink Extension step definition.
    return new Given("the response status code should be $code");
  }

  /**
   * @Then /^I should not get a "(?P<code>[^"]*)" HTTP response$/
   */
  public function iShouldNotGetAHttpResponse($code) {
    // Use the Mink Extension step definition.
    return new Given("the response status code should not be $code");
  }

  /**
   * @Given /^I check the box "(?P<checkbox>[^"]*)"$/
   */
  public function iCheckTheBox($checkbox) {
    // Use the Mink Extension step definition.
    return new Given("I check \"$checkbox\"");
  }

  /**
   * @Given /^I uncheck the box "(?P<checkbox>[^"]*)"$/
   */
  public function iUncheckTheBox($checkbox) {
    // Use the Mink Extension step definition.
    return new Given("I uncheck \"$checkbox\"");
  }

  /**
   * @When /^I select the radio button "(?P<label>[^"]*)" with the id "(?P<id>[^"]*)"$/
   * @TODO convert to mink extension.
   */
  public function iSelectTheRadioButtonWithTheId($label, $id) {
    $element = $this->getSession()->getPage();
    $radiobutton = $element->findById($id);
    if ($radiobutton === NULL) {
      throw new \Exception('Neither label nor id was found');
    }
    $value = $radiobutton->getAttribute('value');
    $labelonpage = $radiobutton->getParent()->getText();
    if ($label != $labelonpage) {
      throw new \Exception("Button with $id has label $labelonpage instead of $label.");
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
  public function iAmAnAnonymousUser() {
    // Verify the user is logged out.
    if ($this->loggedIn()) {
      $this->logout();
    }
  }

  /**
   * Creates and authenticates a user with the given role via Drush.
   *
   * @Given /^I am logged in as a user with the "(?P<role>[^"]*)" role$/
   */
  public function iAmLoggedInWithRole($role) {
    // Check if a user with this role is already logged in.
    if ($this->user && isset($this->user->role) && $this->user->role == $role) {
      return TRUE;
    }

    // Create user (and project)
    $user = (object) array(
      'name' => $this->randomString(8),
      'pass' => $this->randomString(16),
      'role' => $role,
    );
    $user->mail = "{$user->name}@example.com";


    // Create a new user.
    $this->getDriver()->userCreate($user);

    $this->users[] = $this->user = $user;

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
   * Attempts to find a link in a table row containing giving text. This is for
   * administrative pages such as the administer content types screen found at
   * `admin/structure/types`.
   *
   * @Given /^I click "(?P<link>[^"]*)" in the "(?P<row_text>[^"]*)" row$/
   */
  public function iClickInTheRow($link, $row_text) {
    $page = $this->getSession()->getPage();
    $rows = $page->findAll('css', 'tr');
    if (!$rows) {
      throw new \Exception('No rows found on page.');
    }
    $row_found = FALSE;
    foreach ($rows as $row) {
      if (strpos($row->getText(), $row_text) !== FALSE) {
        $row_found = TRUE;
        // Found text in this row, now find link in a cell.
        $cells = $row->findAll('css', 'td');
        if (!$cells) {
          throw new \Exception('No cells found in table row.');
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
      throw new \Exception(sprintf('Found a row containing "%s", but no "%s" link.', $row_text, $link));
    }
    else {
      throw new \Exception(sprintf('Failed to find a row containing "%s"', $row_text));
    }
  }

  /**
   * @} End of defgroup "drupal extensions"
   */
}
