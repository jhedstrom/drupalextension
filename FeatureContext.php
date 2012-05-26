<?php

use Behat\Behat\Context\ClosuredContextInterface,
    Behat\Behat\Context\TranslatedContextInterface,
    Behat\Behat\Context\BehatContext,
    Behat\Behat\Exception\PendingException;
use Behat\Gherkin\Node\PyStringNode,
    Behat\Gherkin\Node\TableNode;
use Symfony\Component\Process\Process;

require 'vendor/.composer/autoload.php';

//
// Require 3rd-party libraries here:
//
//   require_once 'PHPUnit/Autoload.php';
//   require_once 'PHPUnit/Framework/Assert/Functions.php';
//

/**
 * Features context.
 */
class FeatureContext extends BehatContext
{
  /**
   * Initializes context.
   *
   * Every scenario gets its own context object.
   *
   * @param array $parameters 
   *   Context parameters (set them up through behat.yml).
   */
  public function __construct(array $parameters) {
    $this->base_url = $parameters['base_url'];
    $driver = new \Behat\Mink\Driver\Selenium2Driver('firefox', array());
    $firefox = new \Behat\Mink\Session($driver);
    $driver = new \Behat\Mink\Driver\GoutteDriver();
    $goutte = new \Behat\Mink\Session($driver);
    $this->mink = new \Behat\Mink\Mink(array('firefox' => $firefox, 'goutte' => $goutte));
    $this->mink->setDefaultSessionName($parameters['default_browser']);
  }

  /**
   * Destructor function to close open sessions.
   */
   public function __destruct() {
   }

  /**
   * @Given /^(that I|I) am at "([^"]*)"$/
   */
  public function iAmAt($syn, $path)
  {
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
    public function iClick($linkname)
    {
      $session = $this->mink->getSession();
      $element = $session->getPage();
      $result = $element->findLink($linkname);
      if (empty($result)) {
        throw new Exception("No link to ". $linkname ."  on ". $session->getCurrentUrl());
      }
      $result->click();
    }

    /**
   * @When /^I search for "([^"]*)"$/
   */
  public function iSearchFor($searchterm)
  {
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
  public function forIenter($formvalue, $fieldname)
  {
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
  public function iPress($submitbutton)
  {
   $session = $this->mink->getSession();
   $element = $session->getPage();
   $submit = $element->findButton($submitbutton);
   if (empty($submit)) {
     throw new Exception('No submit button at '. $session->getCurrentUrl());
   }
   $submit->click();
  }

  /**
   * @Then /^I should see the link "([^"]*)"$/
   */
  public function iShouldSeeTheLink($linkname)
  {
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
  public function iShouldNotSeeTheLink($linkname)
  {
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
  public function iShouldSeeTheHeading($headingname)
  {
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
   * @Then /^I should see the text "([^"]*)"$/
   */
  public function iShouldSeeTheText($text)
  {
  $session = $this->mink->getSession();
  $element = $session->getPage();
  $result = $element->hasContent($text);
  if ($result === False) {
    throw new Exception("The text ". $text ." was not found ". $session->getCurrentUrl());
    }
  }

  /**
   * @Given /^I see the text "([^"]*)"$/
   */
  public function iSeeTheText($text)
  {
  $session = $this->mink->getSession();
  $element = $session->getPage();
  $result = $element->hasContent($text);
  if ($result === False) {
    throw new Exception("The text ". $text ." was not found ". $session->getCurrentUrl());
    }
  }

 /**
  * @Given /^I see the command "([^"]*)"$/
  */
  public function iSeeTheCommand($command) {
  $page = $this->mink->getSession()
  ->getPage();
  $element = $page->find('css', '#content div.codeblock code');
  if (!empty($element)) {
  $this->gitCommand = $element->getText();
  }
  else {
  throw new Exception('Commands could not be found.');
  }
 }

  /**
  * @When /^I clone the repo$/
  */
  public function iCloneTheRepo() {
    $process = new Process($this->gitCommand);
    $process->setTimeout(3600);
    $process->run();
    if (!$process->isSuccessful()) {
      throw new RuntimeException('The clone did not work. - ' . $process->getErrorOutput());
    }
  }

  /**
  * @Then /^I should have a copy of the cloned anonymous repository$/
  */
  public function iShouldHaveACopyOfTheClonedAnonymousRepository() {
    if (!is_dir('doobie')) {
      throw new Exception('The repo could not be found.');
    }
    $oldDirectory = getcwd();
    chdir('doobie');
    $process = new Process('git log');
    $process->run();
    if (!$process->isSuccessful()) {
      throw new RuntimeException('The history for the repository could  not be found.' . $process->getErrorOutput());
    }
    chdir($oldDirectory);
    $process = new Process('rm -rf doobie');
    $process->run();
    if (!$process->isSuccessful()) {
      throw new Exception('ouch.' . $process->getErrorOutput());
    }
  }

 /**
   * @Given /^I am viewing search results page for term views$/
   */
  public function iAmViewingSearchResultsPageForTermViews() {
    $session = $this->mink->getSession();
    $session->visit($this->base_url .'/search/apachesolr_search/views');
    $element = $session->getPage()
      ->findLink('Views');
    if (empty($element)) {
      throw new Exception('No results for views on the search results page.');
    }
  }

  /**
   * @When /^I look at the sidebar$/
   */
  public function iLookAtTheSidebar() {
    $page = $this->mink->getSession()
      ->getPage();
    $element = $page->find('css', '#column-right');
    if (empty($element)) {
      throw new Exception('No right sidebar found on search results page.');
    }
  }

  /**
   * @Then /^I should see a filter by block$/
   */
  public function iShouldSeeAFilterByBlock() {
    $page = $this->mink->getSession()
      ->getPage();
    $element = $page->find('css', '#block-drupalorg_search-meta_type h2');
    if (empty($element)) {
      throw new Exception('The filter by block was not found.');
    }
  }

  /**
   * @Given /^a search for block$/
   */
  public function aSearchForBlock() {
    $page = $this->mink->getSession()
      ->getPage();
    $element = $page->find('css', '#block-drupalorg_search-drupalorg_search_alternate h2');
    if (empty($element)) {
      throw new Exception('The search for block was not found.');
    }
  }

  // Private function for the whoami step
   private function whoami()
   {
    $session = $this->mink->getSession();
    $element = $session->getPage();
    // go to the user page
    $session->visit($this->base_url . '/user');
    $page_title = $element->find('css', '#page-title')->getText();
    if ($page_title) {
      return $page_title;
    }
    return False;
  }

  /**
   * @Given /^I am logged in as "([^"]*)" with the password "([^"]*)"$/
   */
  public function iAmLoggedInAsWithThePassword($username, $passwd)
  {
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
   * @Given /^for "([^"]*)" I select "([^"]*)"$/
   */
  public function forISelect($arg1, $arg2)
  {
      throw new PendingException();
  }

  /**
   * @Given /^I execute the commands$/
   */
  public function iExecuteTheCommands()
  {
      throw new PendingException();
  }

  /**
   * @Given /^I check "([^"]*)"$/
   */
  public function iCheck($arg1)
  {
      throw new PendingException();
  }

}


