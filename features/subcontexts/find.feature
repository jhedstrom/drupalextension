Feature: Ability to find Drupal sub-contexts
  In order to facilitate maintainable step-definitions
  As a feature developer
  I need to be able to define step-definitions within corresponding Drupal modules or projects

  Background:
    Given a file named "foo.behat.inc" with:
      """
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
      """
    And a file named "features/foo.feature" with:
      """
      Feature: Test foo subcontext

        Scenario: Test foo subcontext
          Given I should have a subcontext definition
      """
    And a file named "behat.yml" with:
      """
      default:
        suites:
          default:
            contexts: [Drupal\DrupalExtension\Context\DrupalContext]
        extensions:
          Drupal\MinkExtension:
            goutte: ~
            selenium2: ~
            base_url: http://drupal.org
          Drupal\DrupalExtension:
            blackbox: ~
            subcontexts:
              paths: { foo: './' }
      """

  Scenario: Step-definitions in sub-contexts are available
    When I run "behat --no-colors -dl"
    Then the output should contain:
      """
      Then /^I should have a subcontext definition$/
      """

  Scenario: Subcontext can be instantiated
    When I run "behat --no-colors"
    Then the output should contain:
      """
      TODO: write pending definition
      """
