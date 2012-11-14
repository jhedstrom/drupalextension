Feature: Ability to find Drupal sub-contexts
  In order to facilitate maintainable step-definitions
  As a feature developer
  I need to be able to define step-definitions within corresponding Drupal modules or projects

  Background:
    Given a file named "foo.behat.inc" with:
      """
      <?php

      use Drupal\DrupalExtension\Context\DrupalSubContextInterface;
      use Behat\Behat\Context\BehatContext;
      use Behat\Behat\Exception\PendingException;

      class FooFoo extends BehatContext implements DrupalSubContextInterface {
        public static function getAlias() {
          return 'foo';
        }

        /**
         * @Then /^I should be logged out$/
         */
        public function iShouldBeLoggedOut() {
          throw new PendingException();
        }
      }
      """

  Scenario: Step-definitions in sub-contexts are available
    Given a file named "behat.yml" with:
      """
      default:
        paths:
          features: 'features'
        extensions:
          Behat\MinkExtension\Extension:
            goutte: ~
            selenium2: ~
            base_url: http://drupal.org
          Drupal\DrupalExtension\Extension:
            blackbox: ~
            subcontext_paths: { foo: './' }
      """
   When I run "behat --no-ansi -dl"
   Then the output should contain:
      """
      Then /^I should be logged out$/
      """
