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
   * Every scenario gets it's own context object.
   *
   * @param array $parameters 
   *   Context parameters (set them up through behat.yml).
   */
  public function __construct(array $parameters) {

    // Allow feature sets to use their own SetFeature.php file
    $this->useContext('git_context', new GitContext($parameters));
    $this->useContext('solr_context', new SolrContext($parameters));
    $this->useContext('drupalorg_context', new DrupalOrgContext($parameters));

  }

  /**
   * Destructor function to close open sessions.
   */
  public function __destruct() {
  }
}
