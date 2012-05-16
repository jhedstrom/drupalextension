<?php

use Behat\Behat\Context\ClosuredContextInterface,
    Behat\Behat\Context\TranslatedContextInterface,
    Behat\Behat\Context\BehatContext,
    Behat\Behat\Exception\PendingException;
use Behat\Gherkin\Node\PyStringNode,
    Behat\Gherkin\Node\TableNode;
use Symfony\Component\Process\Process;

/**
 * Features context.
 */
class DrupalOrgContext extends BehatContext
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
    $this->mink->stopSessions();
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
   * @Then /^I should see a list of modules which mention masquerade$/
   */
  public function iShouldSeeAListOfModulesWhichMentionMasquerade()
  {
	$session = $this->mink->getSession();
	$element = $session->getPage();
	$result = $element->findLink($this->searchterm);
	if (empty($result)) {
		throw new Exception("No link to $this->searchterm on ". $session->getCurrentUrl());
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
    if (!empty($result)) {
      $result->click();
     }
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
}


