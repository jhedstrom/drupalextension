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
class SolrContext extends BehatContext
{
  /**
   * Initializes context.
   *
   * Every scenario gets it's own context object.
   *
   * @param array $parameters 
   *   Context parameters (set them up through behat.yml).
   */
  public function __construct(array $parameters) {
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
   * @Given /^I am viewing search results page for term views$/
   */
  public function iAmViewingSearchResultsPageForTermViews() {
    $session = $this->mink->getSession();
    $session->visit('http://drupal.org/search/apachesolr_search/views');
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
