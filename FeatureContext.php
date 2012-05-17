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
   * @Given /^I am at "([^"]*)"$/
   */
  public function iAmAt($path)
  {
    $session = $this->mink->getSession();
    $session->visit($this->base_url . $path);
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
    $this->searchterm = $searchterm;
    $session = $this->mink->getSession();
    $element = $session->getPage();
    $element->fillField('edit-text', $this->searchterm);
    $submit = $element->findById('edit-submit');
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
   * @Given /^I am viewing a sandbox repository that contains code$/
   */
  public function iAmViewingASandboxRepositoryThatContainsCode() {
    $session = $this->mink->getSession();
    $session->visit($this->base_url .'/sandbox/eliza411/1545884/');
    $element = $session->getPage()
      ->findLink('Version control');
    if (!empty($element)) {
      $element->click();
    }
    else {
      throw new Exception('The version control tab was not found.');
    }
  }

  /**
  * @Given /^I see the Git command to perform an anonymous http clone$/
  */
  public function iSeeTheGitCommandToPerformAnAnonymousHttpClone() {
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
  * @When /^I execute the anonymous http clone$/
  */
  public function iExecuteTheAnonymousHttpClone() {
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
}
