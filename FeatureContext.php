<?php

use Behat\Behat\Context\ClosuredContextInterface,
    Behat\Behat\Context\TranslatedContextInterface,
    Behat\Behat\Context\BehatContext,
    Behat\Behat\Exception\PendingException;
use Behat\Gherkin\Node\PyStringNode,
    Behat\Gherkin\Node\TableNode;
use Symfony\Component\Process\Process;

require 'vendor/autoload.php';

//
// Require 3rd-party libraries here:
//
//   require_once 'PHPUnit/Autoload.php';
//   require_once 'PHPUnit/Framework/Assert/Functions.php';
//

/**
 * Features context.
 */
class FeatureContext extends BehatContext {

  /**
   * Current authenticated user.
   *
   * A value of FALSE denotes an anonymous user.
   */
  public $user = FALSE;

  /**
   * Initializes context.
   *
   * Every scenario gets its own context object.
   *
   * @param array $parameters.
   *   Context parameters (set them up through behat.yml).
   */
  public function __construct(array $parameters) {
    $this->base_url = $parameters['base_url'];
    $this->default_browser = $parameters['default_browser'];
    $this->drushAlias = $parameters['drush_alias'];
  }

  /**
   * @BeforeScenario
   */
  public function beforeScenario($event) {
    $driver = new \Behat\Mink\Driver\Selenium2Driver('firefox', array());
    $firefox = new \Behat\Mink\Session($driver);
    $driver = new \Behat\Mink\Driver\GoutteDriver();
    $goutte = new \Behat\Mink\Session($driver);
    $this->mink = new \Behat\Mink\Mink(array('firefox' => $firefox, 'goutte' => $goutte));
    $this->mink->setDefaultSessionName($this->default_browser);
  }

  /**
   * @AfterScenario
   */
  public function afterScenario($event) {
    $this->mink->stopSessions();
    unset($this->mink);
  }

  /**
   * Helper function to generate a random string of arbitrary length.
   *
   * Copied from drush_generate_password().
   *
   * @param $length
   *   Number of characters the generated string should contain.
   * @return
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

    $session = $this->mink->getSession();
    $session->visit($this->base_url . '/user');
    $element = $session->getPage();
    $element->fillField('Username', $this->user->name);
    $element->fillField('Password', $this->user->pass);
    $submit = $element->findButton('Log in');
    if (empty($submit)) {
      throw new Exception('No submit button at '. $session->getCurrentUrl());
    }

    // Log in.
    $submit->click();
  }

  /**
   * Helper function to logout.
   */
  public function logout() {
    $session = $this->mink->getSession();
    $session->visit($this->base_url . '/user/logout');
  }

  /**
   * Determine if the a user is already logged in.
   */
  public function loggedIn() {
    $session = $this->mink->getSession();
    $session->visit($this->base_url);

    // If a logout link is found, we are logged in. While not perfect, this is
    // how Drupal SimpleTests currently work as well.
    $element = $session->getPage();
    return $element->findLink('Log out');
  }

  /**
   * @Given /^(that I|I) am at "([^"]*)"$/
   */
  public function iAmAt($syn, $path) {
    $session = $this->mink->getSession();
    $session->visit($this->base_url . $path);
    return;
    $status = $session->getStatusCode();
    if ($status != 200) {
      throw new Exception("Status $status when retriving ". $session->getCurrentUrl());
    }
  }

  /**
   * @When /^I visit "([^"]*)"$/
   */
  public function iVisit($path) {
    $session = $this->mink->getSession();
    $session->visit($this->base_url . $path);
    return;
    $status = $session->getStatusCode();
    if ($status != 200) {
      throw new Exception("Status $status when retriving ". $session->getCurrentUrl());
    }
  }


  /**
   * @When /^I click "([^"]*)"$/
   */
  public function iClick($linkname) {
    $session = $this->mink->getSession();
    $element = $session->getPage();
    $result = $element->findLink($linkname);
    if (empty($result)) {
      throw new Exception("No link to ". $linkname ." on ". $session->getCurrentUrl());
    }
    $result->click();
  }

  /**
   * @When /^I search for "([^"]*)"$/
   */
  public function iSearchFor($searchterm) {
    $session = $this->mink->getSession();
    $element = $session->getPage();
    $element->fillField('edit-text', $searchterm);
    $submit = $element->findById('edit-submit');
    if (empty($submit)) {
      throw new Exception('No submit button at '. $session->getCurrentUrl());
    }
    $submit->click();
  }

  /**
   * @Given /^for "([^"]*)" I enter "([^"]*)"$/
   */
  public function forIenter($fieldname, $formvalue) {
    $session = $this->mink->getSession();
    $element = $session->getPage();
    $result = $element->hasField($fieldname);
    if ($result === False) {
      throw new Exception("No field ". $fieldname ." found.");
    }
    $element->fillField($fieldname, $formvalue);
  }

  /**
   * @When /^I press "([^"]*)"$/
   */
  public function iPress($submitbutton) {
    $session = $this->mink->getSession();
    $element = $session->getPage();
    $submit = $element->findButton($submitbutton);
    if (empty($submit)) {
      throw new Exception('No submit button at '. $session->getCurrentUrl());
    }
    $session->wait(5000);
    $submit->click();
  }

  /**
   * @Then /^I should see the link "([^"]*)"$/
   */
  public function iShouldSeeTheLink($linkname) {
    $session = $this->mink->getSession();
    $element = $session->getPage();
    $result = $element->findLink($linkname);
    if (empty($result)) {
      throw new Exception("No link to ". $linkname ."  on ". $session->getCurrentUrl());
    }
  }

  /**
   * @Then /^I should not see the link "([^"]*)"$/
   */
  public function iShouldNotSeeTheLink($linkname) {
    $session = $this->mink->getSession();
    $element = $session->getPage();
    $result = $element->findLink($linkname);
    if ($result) {
      throw new Exception("The link ". $linkname ." was present on ". $session->getCurrentUrl() ." and was not supposed to be.");
    }
  }

  /**
   * @Then /^I should see the heading "([^"]*)"$/
   */
  public function iShouldSeeTheHeading($headingname) {
    $session = $this->mink->getSession();
    $element = $session->getPage();
    foreach (array('h1', 'h2', 'h3', 'h4', 'h5', 'h6') as $heading) {
      $results = $element->findAll('css', $heading);
      foreach ($results as $result) {
        if ($result->getText() == $headingname) {
          return;
        }
      }
    }
    throw new Exception("The text ". $headingname ." was not found in any heading ". $session->getCurrentUrl());
  }

  /**
   * @Then /^(I|I should) see the text "([^"]*)"$/
   */
  public function iShouldSeeTheText($syn, $text) {
    $session = $this->mink->getSession();
    $element = $session->getPage();
    $result = $element->hasContent($text);
    if ($result === False) {
      throw new Exception("The text ". $text ." was not found ". $session->getCurrentUrl());
    }
  }

  /**
   * @Then /^I should not see the text "([^"]*)"$/
   */
  public function iShouldNotSeeTheText($text) {
    $session = $this->mink->getSession();
    $element = $session->getPage();
    $result = $element->hasContent($text);
    if ($result === True) {
      throw new Exception("The text ". $text ." was found on ". $session->getCurrentUrl() ." and should not have been");
    }
  }

  /**
   * @When /^I clone the repo$/
   */
  public function iCloneTheRepo() {
    $session = $this->mink->getSession();
    $element = $session->getPage();
    $result =  $element->find('css', '#content div.codeblock code');
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
    $oldDirectory = getcwd();
    chdir($repo);
    $process = new Process('git log');
    $process->run();
    if (!$process->isSuccessful()) {
      throw new RuntimeException('The history for the repository could  not be found.' . $process->getErrorOutput());
    }
    chdir($oldDirectory);
    $process = new Process('rm -rf '. $repo);
    $process->run();
    if (!$process->isSuccessful()) {
      throw new Exception('ouch.' . $process->getErrorOutput());
    }
  }

  /**
   * Private function for the whoami step.
   */
  private function whoami() {
    $session = $this->mink->getSession();
    $element = $session->getPage();
    // go to the user page
    $session->visit($this->base_url . '/user');
    if ($find = $element->find('css', '#page-title')) {
      $page_title = $find->getText();
      if ($page_title) {
        return $page_title;
      }
    }
    return False;
  }

  /**
   * @Given /^I am logged in as a user with the "([^"]*)" role$/
   */
  public function iAmLoggedInWithRole($role) {
    // Create user.
    $name = $this->randomString(8);
    $pass = $this->randomString(16);

    // Create a new user.
    $process = new Process("drush @{$this->drushAlias} user-create --password={$pass} $name");
    $process->setTimeout(3600);
    $process->run();

    $this->user = (object) array(
      'name' => $name,
      'pass' => $pass,
    );

    if ($role == 'authenticated user') {
      // Nothing to do.
    }
    else {
      // Assign the given role.
      $process = new Process("drush @{$this->drushAlias} \"{$role}\" {$name}");
      $process->setTimeout(3600);
      $process->run();
    }

    // Login.
    $this->login();
  }

  /**
   * @Given /^I am logged in as "([^"]*)" with the password "([^"]*)"$/
   */
  public function iAmLoggedInAsWithThePassword($username, $passwd) {
    $user = $this->whoami();
    if(strtolower($user) == strtolower($username)) {
      // Already logged in.
      return;
    }

    $session = $this->mink->getSession();
    $element = $session->getPage();

    if ($user != 'User account') {
      // Logout
      $session->visit($this->base_url . '/user/logout');
    }

    // go to the user page
    $session->visit($this->base_url . '/user');
    // get the page title
    $page_title = $element->findByID('page-title')->getText();
    if ($page_title == 'User account') {
      // If I see this, I'm not logged in at all so log in
      $element->fillField('Username', $username);
      $element->fillField('Password', $passwd);
      $submit = $element->findButton('Log in');
      if (empty($submit)) {
        throw new Exception('No submit button at '. $session->getCurrentUrl());
      }
      // log in
      $submit->click();
      $user = $this->whoami();
      if(strtolower($user) == strtolower($username)) {
        // Successfully logged in.
        return;
      }
    } else {
      throw new Exception("Failed to reach the login page.");
    }

    throw new Exception('Not logged in.');
  }

  /**
   * @Given /^I execute the commands$/
   */
  public function iExecuteTheCommands() {
    throw new PendingException();
  }

  /**
   * @Given /^I check "([^"]*)"$/
   */
  public function iCheck($checkbox) {
    $session = $this->mink->getSession();
    $element = $session->getPage();
    $result = $element->findField($checkbox);
    if ($result->isChecked()) {
      throw new Exception("User has already agreed");
    }
    $result->check();
  }

  /**
   * @Given /^I uncheck "([^"]*)"$/
   */
  public function iUncheck($box) {
    $session = $this->mink->getSession();
    $element = $session->getPage();
    $result = $element->findField($box);
    if ($result->isChecked()) {
      $result->uncheck();
    }
    throw new Exception("User had not agreed");
  }

  /**
   * @When /^I select the radio button "([^"]*)" with id "([^"]*)"$/
   */
  public function iSelectTheRadioButtonWithId($label, $id) {
    $session = $this->mink->getSession();
    $element = $session->getPage();
    $radiobutton = $element->findById($id);
    if ($radiobutton === null) {
      throw new Exception('Neither label nor id was found');
    }
    $value = $radiobutton->getAttribute('value');
    $labelonpage = $radiobutton->getParent()->getText();
    if ($label != $labelonpage) {
      throw new Exception("Button with $id has label $labelonpage instead of $label.");
    }
    $radiobutton->selectOption($value, False);
  }
}
