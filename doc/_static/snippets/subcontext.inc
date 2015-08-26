<?php

/**
 * Contains \FooFoo.
 */

use Behat\Behat\Hook\Scope\BeforeScenarioScope;
use Behat\Behat\Tester\Exception\PendingException;
use Drupal\DrupalExtension\Context\DrupalSubContextBase;
use Drupal\DrupalExtension\Context\DrupalSubContextInterface;

/**
 * Example subcontext.
 */
class FooFoo extends DrupalSubContextBase implements DrupalSubContextInterface {

  /**
   * @var \Drupal\DrupalExtension\Context\DrupalContext
   */
  protected $drupalContext;

  /**
   * @var \Drupal\DrupalExtension\Context\MinkContext
   */
  protected $minkContext;

  /**
   * @BeforeScenario
   */
  public function gatherContexts(BeforeScenarioScope $scope) {
    $environment = $scope->getEnvironment();

    $this->drupalContext = $environment->getContext('Drupal\DrupalExtension\Context\DrupalContext');
    $this->minkContext = $environment->getContext('Drupal\DrupalExtension\Context\MinkContext');
  }

  /**
   * @Given I create a(an) :arg1 content type
   */
  public function CreateAContentType($arg1) {
    $this->minkContext->assertAtPath("admin/structure/types/add");
    $node = [
      'title' => 'Test content!',
    ];
    $this->drupalContext->nodeCreate($node);
  }

  /**
   * @Then /^I should have a subcontext definition$/
   */
  public function assertSubContextDefinition() {
    throw new PendingException();
  }

}
