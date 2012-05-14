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
   * @Then /^I should see a link called "([^"]*)"$/
   */
  public function iShouldSeeALinkCalled($linkname)
  {
  $session = $this->mink->getSession();
  $element = $session->getPage();
  $result = $element->findLink($linkname);
  if (empty($result)) {
      throw new Exception("No link to ". $linkname ."  on ". $session->getCurrentUrl());
    }
  }
}


