<?php

use Drupal\DrupalExtension\Context\DrupalContext,
    Behat\Behat\Exception\PendingException;
use Behat\Gherkin\Node\PyStringNode,
    Behat\Gherkin\Node\TableNode;

require_once 'PHPUnit/Autoload.php';
require_once 'PHPUnit/Framework/Assert/Functions.php';

require 'vendor/autoload.php';

/**
 * Features context for custom step-definitions.
 *
 * @todo we are duplicating code from Behat's FeatureContext here for the
 * purposes of testing since we can't easily run that as a subcontext due to
 * naming conflicts.
 */
class FeatureContext extends DrupalContext {
  /**
   * Initialize the needed step definitions for subcontext testing.
   */
  public function __construct() {
    $this->useContext('behat_feature_context', new BehatFeatureContext());
  }
}
