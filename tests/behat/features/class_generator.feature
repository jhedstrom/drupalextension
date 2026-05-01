@class_generator
Feature: Class generator integration

  In order to scaffold context classes that integrate with the Drupal Extension
  As a Behat user with the Drupal Extension enabled
  I want 'behat --init' to produce a starter context class extending RawDrupalContext

  @test-blackbox
  Scenario: Assert "behat --init" generates a context class extending 'RawDrupalContext' for a non-namespaced suite context
    Given a file named "behat.yml" with:
      """
      default:
        suites:
          default:
            contexts:
              - GeneratedFeatureContext
        extensions:
          Drupal\MinkExtension:
            browserkit_http: ~
            base_url: http://blackbox
          Drupal\DrupalExtension:
            blackbox: ~
      """
    When I run "behat --init --no-colors"
    Then it should pass with:
      """
      """
    And "features/bootstrap/GeneratedFeatureContext.php" file should contain:
      """
      <?php

      use Drupal\DrupalExtension\Context\RawDrupalContext;
      use Behat\Gherkin\Node\PyStringNode;
      use Behat\Gherkin\Node\TableNode;
      use Behat\Behat\Tester\Exception\PendingException;

      /**
       * Defines application features from the specific context.
       */
      class GeneratedFeatureContext extends RawDrupalContext {

        /**
         * Initializes context.
         *
         * Every scenario gets its own context instance.
         * You can also pass arbitrary arguments to the
         * context constructor through behat.yml.
         */
        public function __construct() {
        }

      }
      """

  @test-blackbox
  Scenario: Assert "behat --init" generates a namespaced context class extending 'RawDrupalContext'
    Given a file named "behat.yml" with:
      """
      default:
        suites:
          default:
            contexts:
              - App\Tests\Behat\GeneratedFeatureContext
        extensions:
          Drupal\MinkExtension:
            browserkit_http: ~
            base_url: http://blackbox
          Drupal\DrupalExtension:
            blackbox: ~
      """
    When I run "behat --init --no-colors"
    Then it should pass with:
      """
      """
    And "features/bootstrap/App/Tests/Behat/GeneratedFeatureContext.php" file should contain:
      """
      <?php

      namespace App\Tests\Behat;

      use Drupal\DrupalExtension\Context\RawDrupalContext;
      use Behat\Gherkin\Node\PyStringNode;
      use Behat\Gherkin\Node\TableNode;
      use Behat\Behat\Tester\Exception\PendingException;

      /**
       * Defines application features from the specific context.
       */
      class GeneratedFeatureContext extends RawDrupalContext {

        /**
         * Initializes context.
         *
         * Every scenario gets its own context instance.
         * You can also pass arbitrary arguments to the
         * context constructor through behat.yml.
         */
        public function __construct() {
        }

      }
      """
