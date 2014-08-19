Feature: Ability to find Drupal sub-contexts
  In order to facilitate maintainable step-definitions
  As a feature developer
  I need to be able to define step-definitions within corresponding Drupal modules or projects

  Background:
    Given a file named "foo.behat.inc" with:
      """
      <?php

      use Behat\Behat\Exception\PendingException;

      use Drupal\DrupalExtension\Context\DrupalSubContextInterface;
      use Drupal\DrupalExtension\Context\DrupalContext;

      class FooFoo implements DrupalSubContextInterface {

        private $drupalContext;

        public function __construct(DrupalContext $context) {
          $this->drupalContext = $context;
        }

        /**
         * @Then /^I should have a subcontext definition$/
         */
        public function assertSubContextDefinition() {
          throw new PendingException();
        }
      }
      """

  Scenario: Step-definitions in sub-contexts are available
    Given a file named "behat.yml" with:
      """
      default:
        suites:
          default:
            contexts: [Drupal\DrupalExtension\Context\DrupalContext]
        extensions:
          Behat\MinkExtension:
            goutte: ~
            selenium2: ~
            base_url: http://drupal.org
          Drupal\DrupalExtension:
            blackbox: ~
            subcontexts:
              paths: { foo: './' }
      """
   When I run "behat --no-colors -dl"
   Then the output should contain:
      """
      Then /^I should have a subcontext definition$/
      """
