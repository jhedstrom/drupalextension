<?php

namespace Drupal\DrupalExtension\Context;

use Behat\MinkExtension\Context\MinkContext;
use Behat\Behat\Exception\PendingException;
use Behat\Mink\Exception\UnsupportedDriverActionException;
use Symfony\Component\Process\Process;

use Behat\Behat\Context\Step\Given;
use Behat\Behat\Context\Step\When;
use Behat\Behat\Context\Step\Then;

use Behat\Mink\Driver\Selenium2Driver as Selenium2Driver;

/**
 * Features context.
 */
class DrupalContext extends MinkContext {

  /**
   * Array of parameters for the Drupal context.
   */
  public $parameters = array();

  /**
   * Basic auth user and password.
   */
  public $basic_auth = array();

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
   * Store a drush alias for tests requiring shell access.
   */
  public $drushAlias = FALSE;

  /**
   * Run before every scenario.
   *
   * @BeforeScenario
   */
  public function beforeScenario($event) {
    if (!empty($this->basic_auth)) {
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
        $process = new Process("drush @{$this->drushAlias} user-cancel --yes {$user->name} --delete-content");
        $process->setTimeout(3600);
        $process->run();
        if (!$process->isSuccessful()) {
          throw new \RuntimeException($process->getErrorOutput());
        }
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
    $element->fillField('Username', $this->user->name);
    $element->fillField('Password', $this->user->pass);
    $submit = $element->findButton('Log in');
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
    return $element->findLink('Log out');
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
   * Wrapper step definition to Mink Extension that additionally checks for a
   * valid HTTP 200 response if available.
   *
   * @Given /^(?:that I|I) am at "(?P<path>[^"]*)"$/
   *
   * @throws UnsupportedDriverActionException
   */
  public function iAmAt($path) {
    $return = array();
    // Use the Mink Extenstion step definition.
    $return[] = new Given("I am on \"$path\"");

    // If available, add extra validation that this is a 200 response.
    try {
      $this->getSession()->getStatusCode();
      $return[] = new Given('I should get a "200" HTTP response');
    }
    catch (UnsupportedDriverActionException $e) {
      // Simply continue on, as this driver doesn't support HTTP response codes.
    }

    return $return;
  }

  /**
   * @When /^I visit "(?P<path>[^"]*)"$/
   */
  public function iVisit($path) {
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
   * @Given /^I am logged in as a user with the "([^"]*)" role$/
   */
  public function iAmLoggedInWithRole($role) {
    // Check if a user with this role is already logged in.
    if ($this->user && isset($this->user->role) && $this->user->role == $role) {
      return TRUE;
    }

    // Create user (and project)
    $name = $this->randomString(8);
    $pass = $this->randomString(16);

    // Create a new user.
    $process = new Process("drush @{$this->drushAlias} user-create --password={$pass} --mail=$name@example.com $name");
    $process->setTimeout(3600);
    $process->run();
    if (!$process->isSuccessful()) {
      throw new \RuntimeException($process->getErrorOutput());
    }

    $this->users[] = $this->user = (object) array(
      'name' => $name,
      'pass' => $pass,
      'role' => $role,
    );

    if ($role == 'authenticated user') {
      // Nothing to do.
    }
    else {
      // Assign the given role.
      $process = new Process("drush @{$this->drushAlias} user-add-role \"{$role}\" {$name}");
      $process->setTimeout(3600);
      $process->run();
      if (!$process->isSuccessful()) {
        throw new \RuntimeException($process->getErrorOutput());
      }
    }

    // Login.
    $this->login();

    return TRUE;
  }

  /**
   * @} End of defgroup "drupal extensions"
   */
}
