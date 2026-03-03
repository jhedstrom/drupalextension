@behatcli @blackbox
Feature: Behat CLI context

  Tests for BehatCliContext functionality that is used to test Behat Steps traits
  by running Behat through CLI.

  - Assert that BehatCliContext context itself can be bootstrapped by Behat,
  including failed runs assertions.

  Background:
    Given a file named "features/bootstrap/FeatureContext.php" with:
      """
      <?php
      use Behat\Behat\Context\Context;
      use Behat\Gherkin\Node\PyStringNode;

      class FeatureContext implements Context {

        /**
         * @Given a passing step
         */
        public function aPassingStep() {
        }

        /**
         * @Then I throw test exception with message :message
         */
        public function throwTestException($message) {
          throw new \RuntimeException($message);
        }

        /**
         * @Then I fail with message :message
         */
        public function failWithMessage($message) {
          throw new \Exception($message);
        }

        /**
         * @Given /^(?:there is )?a file named "([^"]*)" with:$/
         */
        public function aFileNamedWith($filename, PyStringNode $content) {
          $path = dirname($filename);
          if (!is_dir($path) && $path !== '.') {
            mkdir($path, 0777, true);
          }
          file_put_contents($filename, (string) $content);
        }

        /**
         * @Then /^file "([^"]*)" should exist$/
         */
        public function fileShouldExist($path) {
          if (!file_exists($path)) {
            throw new \RuntimeException(sprintf('File "%s" does not exist.', $path));
          }
        }
      }
      """
    And a file named "behat.yml" with:
      """
      default:
        suites:
          default:
            contexts:
              - FeatureContext
      """

  Scenario: Test passes
    Given a file named "features/test.feature" with:
      """
      Feature: Test
        Scenario: A passing scenario
          Given a passing step
      """
    When I run "behat --no-colors"
    Then it should pass

  Scenario: Test fails
    Given a file named "features/test.feature" with:
      """
      Feature: Test
        Scenario: A failing scenario
          Given a passing step
          Then I fail with message "Expected failure"
      """
    When I run "behat --no-colors"
    Then it should fail

  Scenario: Test fails with exception
    Given a file named "features/test.feature" with:
      """
      Feature: Test
        Scenario: An exception scenario
          Given a passing step
          Then I throw test exception with message "Intentional error"
      """
    When I run "behat --no-colors"
    Then it should fail with:
      """
      Intentional error (RuntimeException)
      """

  Scenario: Test nested PyStrings using triple single quotes
    And scenario steps:
      """
      Given a file named "test.txt" with:
        '''
        Line one of content
        Line two of content
        Line three of content
        '''
      Then file "test.txt" should exist
      """
    When I run "behat --no-colors"
    Then it should pass
