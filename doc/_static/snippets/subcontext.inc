<?php
use Behat\Behat\Tester\Exception\PendingException;
use Drupal\DrupalExtension\Context\DrupalSubContextInterface;
use Drupal\DrupalDriverManager;
  class FooFoo implements DrupalSubContextInterface {
    private $drupal;
    public function __construct(DrupalDriverManager $drupal) {
      $this->drupal = $drupal;
    }
    /**
      * @Then /^I should have a subcontext definition$/
     */
    public function assertSubContextDefinition() {
      throw new PendingException();
    }
  }
