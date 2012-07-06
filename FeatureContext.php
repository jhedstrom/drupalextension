<?php

use Behat\Symfony2Extension\Context\KernelAwareInterface;
use Behat\MinkExtension\Context\MinkContext;
use Behat\Behat\Context\ClosuredContextInterface,
    Behat\Behat\Context\TranslatedContextInterface,
    Behat\Behat\Context\BehatContext,
    Behat\Behat\Exception\PendingException;
use Behat\Gherkin\Node\PyStringNode,
    Behat\Gherkin\Node\TableNode;
use Symfony\Component\Process\Process;

use Behat\Behat\Context\Step\Given;
use Behat\Behat\Context\Step\When;
use Behat\Behat\Context\Step\Then;

require 'vendor/autoload.php';

/**
 * Features context.
 */
class FeatureContext extends MinkContext {

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
   * Initializes context.
   *
   * Every scenario gets its own context object.
   *
   * @param array $parameters.
   *   Context parameters (set them up through behat.yml).
   */
  public function __construct(array $parameters) {
    if (isset($parameters['basic_auth'])) {
      $this->basic_auth = $parameters['basic_auth'];
    }
    $this->default_browser = $parameters['default_browser'];
    $this->drushAlias = $parameters['drush_alias'];
  }

  /**
   * Run before every scenario.
   *
   * @BeforeScenario
   */
  public function beforeScenario($event) {
    if (isset($this->basic_auth)) {
      // Setup basic auth.
      $this->getSession()->setBasicAuth($this->basic_auth['username'], $this->basic_auth['password']);
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
          throw new RuntimeException($process->getErrorOutput());
        }
      }
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
      throw new Exception('Tried to login without a user.');
    }

    $this->getSession()->visit($this->locatePath('/user'));
    $element = $this->getSession()->getPage();
    $element->fillField('Username', $this->user->name);
    $element->fillField('Password', $this->user->pass);
    $submit = $element->findButton('Log in');
    if (empty($submit)) {
      throw new Exception('No submit button at ' . $this->getSession()->getCurrentUrl());
    }

    // Log in.
    $submit->click();

    if (!$this->loggedIn()) {
      throw new Exception("Failed to log in as user \"{$this->user->name}\" with role \"{$this->user->role}\".");
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
   * Private function for the whoami step.
   */
  private function whoami() {
    $element = $this->getSession()->getPage();
    // Go to the user page.
    $session->visit($this->locatePath('/user'));
    if ($find = $element->find('css', '#page-title')) {
      $page_title = $find->getText();
      if ($page_title) {
        return $page_title;
      }
    }
    return FALSE;
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
   * @Given /^(?:that I|I) am at "([^"]*)"$/
   */
  public function iAmAt($path) {
    // Use the Mink Extenstion step definition.
    return new Given("I am on \"$path\"");
  }

  /**
   * @When /^I visit "([^"]*)"$/
   */
  public function iVisit($path) {
    // Use the Mink Extenstion step definition.
    return new Given("I am on \"$path\"");
  }


  /**
   * @When /^I click "([^"]*)"$/
   */
  public function iClick($linkname) {
    // Use the Mink Extenstion step definition.
    return new Given("I follow \"$linkname\"");
  }

  /**
   * @Given /^for "([^"]*)" I enter "([^"]*)"$/
   * @Given /^I enter "([^"]*)" for "([^"]*)"$/
   */
  public function forIenter($fieldname, $formvalue) {
    // Use the Mink Extenstion step definition.
    return new Given("I fill in \"$fieldname\" with \"$formvalue\"");
  }

  /**
   * @When /^I press the "([^"]*)" button$/
   */
  public function iPressTheButton($button) {
    // Use the Mink Extenstion step definition.
    return new Given("I press \"$button\"");
  }

  /**
   * @Then /^I should see the link "([^"]*)"$/
   */
  public function iShouldSeeTheLink($linkname) {
    $element = $this->getSession()->getPage();
    $result = $element->findLink($linkname);
    if (empty($result)) {
      throw new Exception("No link to " . $linkname . " on " . $session->getCurrentUrl());
    }
  }

  /**
   * @Then /^I should not see the link "([^"]*)"$/
   */
  public function iShouldNotSeeTheLink($linkname) {
    $element = $this->getSession()->getPage();
    $result = $element->findLink($linkname);
    if ($result) {
      throw new Exception("The link " . $linkname . " was present on " . $session->getCurrentUrl() . " and was not supposed to be.");
    }
  }

  /**
   * @Then /^I should see the heading "([^"]*)"$/
   */
  public function iShouldSeeTheHeading($headingname) {
    $element = $this->getSession()->getPage();
    foreach (array('h1', 'h2', 'h3', 'h4', 'h5', 'h6') as $heading) {
      $results = $element->findAll('css', $heading);
      foreach ($results as $result) {
        if ($result->getText() == $headingname) {
          return;
        }
      }
    }
    throw new Exception("The text " . $headingname . " was not found in any heading " . $session->getCurrentUrl());
  }

  /**
   * @Then /^(I|I should) see the text "([^"]*)"$/
   */
  public function iShouldSeeTheText($syn, $text) {
    $element = $this->getSession()->getPage();
    $result = $element->hasContent($text);
    if ($result === FALSE) {
      throw new Exception("The text " . $text . " was not found " . $session->getCurrentUrl());
    }
  }

  /**
   * @Then /^I should not see the text "([^"]*)"$/
   */
  public function iShouldNotSeeTheText($text) {
    $element = $this->getSession()->getPage();
    $result = $element->hasContent($text);
    if ($result === TRUE) {
      throw new Exception("The text " . $text . " was found on " . $session->getCurrentUrl() . " and should not have been");
    }
  }

  /**
   * @Then /^I should get a "([^"]*)" HTTP response$/
   */
  public function iShouldGetAHttpResponse($status_code) {
    // Use the mink extensions.
    return new Given("the response status code should be $status_code");
  }

  /**
   * @Then /^I should not get a "([^"]*)" HTTP response$/
   */
  public function iShouldNotGetAHttpResponse($status_code) {
    // Use the mink extensions.
    return new Given("the response status code should not be $status_code");
  }

  /**
   * @Given /^I check the box "([^"]*)"$/
   * @TODO convert to mink extension.
   */
  public function iCheckTheBox($checkbox) {
    $element = $this->getSession()->getPage();
    $result = $element->findField($checkbox);
    $checked_state = $result->isChecked();
    if ($checked_state === TRUE) {
      throw new Exception($checkbox . ': Already checked');
    }
    $result->check();
  }

  /**
   * @Given /^I uncheck the box "([^"]*)"$/
   * @TODO convert to mink extension.
   */
  public function iUncheckTheBox($checkbox) {
    $element = $this->getSession()->getPage();
    $result = $element->findField($checkbox);
    $checked_state = $result->isChecked();
    if ($checked_state === TRUE) {
      $result->uncheck();
    }
    else {
      throw new Exception('"' . $checkbox . '" was not checked so it could not be unchecked');
    }
  }

  /**
   * @When /^I select the radio button "([^"]*)" with the id "([^"]*)"$/
   * @TODO convert to mink extension.
   */
  public function iSelectTheRadioButtonWithTheId($label, $id) {
    $element = $this->getSession()->getPage();
    $radiobutton = $element->findById($id);
    if ($radiobutton === NULL) {
      throw new Exception('Neither label nor id was found');
    }
    $value = $radiobutton->getAttribute('value');
    $labelonpage = $radiobutton->getParent()->getText();
    if ($label != $labelonpage) {
      throw new Exception("Button with $id has label $labelonpage instead of $label.");
    }
    $radiobutton->selectOption($value, FALSE);
  }

  /**
   * @} End of defgroup "mink extensions"
   */

  /**
   * @defgroup drupal.org
   * @{
   * Drupal.org-specific step definitions.
   */

  /**
   * @When /^I clone the repo$/
   */
  public function iCloneTheRepo() {
    //mypath stores the last path visited in another iAmAt  step.
    $element = $this->getSession()->getPage($this->mypath);
    $result = $element->find('css', '#content div.codeblock code');
    if (!empty($result)) {
      $this->repo = $result->getText();
    }
    $process = new Process($this->repo);
    $process->setTimeout(3600);
    $process->run();
    if (!$process->isSuccessful()) {
      throw new RuntimeException('The clone did not work. - ' . $process->getErrorOutput());
    }
  }

  /**
   * @Then /^I should have a local copy of "([^"]*)"$/
   */
  public function iShouldHaveALocalCopyOf($repo) {
    if (!is_dir($repo)) {
      throw new Exception('The repo could not be found.');
    }
    $old_directory = getcwd();
    chdir($repo);
    $process = new Process('git log');
    $process->run();
    if (!$process->isSuccessful()) {
      throw new RuntimeException('The history for the repository could  not be found.' . $process->getErrorOutput());
    }
    chdir($old_directory);
    $process = new Process('rm -rf ' . $repo);
    $process->run();
    if (!$process->isSuccessful()) {
      throw new Exception('ouch.' . $process->getErrorOutput());
    }
  }

  /**
   * @When /^I create a project$/
   */
  public function iCreateAProject() {
    $this->project = $this->user->name;
    $element = $this->getSession()->getPage();
    $result = $element->hasField('Project title');
    if ($result === False) {
      throw new Exception("No Project title field was found.");
    }
    $element->fillField('Project title', $this->project);
  }

  /**
   * @Then /^I should see the project$/
   */
  public function iShouldSeeTheProject() {
    $element = $this->getSession()->getPage();
    $result = $element->hasContent($this->project);
    if ($result === FALSE) {
      throw new Exception("The text " . $this->project . " was not found " . $session->getCurrentUrl());
    }
  }

  /**
   * @When /^I search for "([^"]*)"$/
   */
  public function iSearchFor($searchterm) {
    $element = $this->getSession()->getPage();
    $element->fillField('edit-text', $searchterm);
    $submit = $element->findById('edit-submit');
    if (empty($submit)) {
      throw new Exception('No submit button at ' . $session->getCurrentUrl());
    }
    $submit->click();
  }

  /**
   * @} End of defgroup "drupal.org"
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
      throw new RuntimeException($process->getErrorOutput());
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
        throw new RuntimeException($process->getErrorOutput());
      }
    }

    // Login.
    $this->login();

    return TRUE;
  }

  /**
   * @Given /^I am logged in as "([^"]*)" with the password "([^"]*)"$/
   */
  public function iAmLoggedInAsWithThePassword($username, $passwd) {
    $user = $this->whoami();
    if (strtolower($user) == strtolower($username)) {
      // Already logged in.
      return;
    }

    $element = $this->getSession()->getPage();

    if ($user != 'User account') {
      // Logout.
      $this->getSession()->visit($this->locatePath('/user/logout'));
    }

    // Go to the user page.
    $this->getSession()->visit($this->locatePath('/user'));
    // Get the page title.
    $page_title = $element->findByID('page-title')->getText();
    if ($page_title == 'User account') {
      // If I see this, I'm not logged in at all so log in.
      $element->fillField('Username', $username);
      $element->fillField('Password', $passwd);
      $submit = $element->findButton('Log in');
      if (empty($submit)) {
        throw new Exception('No submit button at ' . $session->getCurrentUrl());
      }
      // Log in.
      $submit->click();
      $user = $this->whoami();
      if (strtolower($user) == strtolower($username)) {
        // Successfully logged in.
        return;
      }
    }
    else {
      throw new Exception("Failed to reach the login page.");
    }

    throw new Exception('Not logged in.');
  }

  /**
   * @} End of defgroup "drupal extensions"
   */

  /**
   * @Given /^I execute the commands$/
   */
  public function iExecuteTheCommands() {
    throw new PendingException();
  }
}
