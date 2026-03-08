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

  Scenario: Entity hooks accept array callable for unresolvable context class
    Given some behat configuration
    And a file named "features/bootstrap/FeatureContext.php" with:
      """
      <?php
      use Drupal\DrupalExtension\Context\DrupalContext;
      use Drupal\DrupalExtension\Hook\Scope\BeforeNodeCreateScope;
      use Drupal\DrupalExtension\Hook\Scope\BeforeUserCreateScope;
      use Drupal\DrupalExtension\Hook\Scope\BeforeTermCreateScope;
      use Drupal\DrupalExtension\Hook\Scope\EntityScope;

      class FeatureContext extends DrupalContext {

        private static array $hooksCalled = [];

        /**
         * @BeforeNodeCreate
         */
        public static function beforeNodeCreate(BeforeNodeCreateScope $scope) {
          self::$hooksCalled[] = 'BeforeNodeCreate';
        }

        /**
         * @AfterNodeCreate
         */
        public static function afterNodeCreate(EntityScope $scope) {
          self::$hooksCalled[] = 'AfterNodeCreate';
        }

        /**
         * @BeforeUserCreate
         */
        public static function beforeUserCreate(BeforeUserCreateScope $scope) {
          self::$hooksCalled[] = 'BeforeUserCreate';
        }

        /**
         * @AfterUserCreate
         */
        public static function afterUserCreate(EntityScope $scope) {
          self::$hooksCalled[] = 'AfterUserCreate';
        }

        /**
         * @BeforeTermCreate
         */
        public static function beforeTermCreate(BeforeTermCreateScope $scope) {
          self::$hooksCalled[] = 'BeforeTermCreate';
        }

        /**
         * @AfterTermCreate
         */
        public static function afterTermCreate(EntityScope $scope) {
          self::$hooksCalled[] = 'AfterTermCreate';
        }

        /**
         * @Then the :hook hook should have been called
         */
        public function assertHookCalled($hook) {
          if (!in_array($hook, self::$hooksCalled)) {
            throw new \RuntimeException(sprintf('Expected hook "%s" was not called. Called hooks: %s', $hook, implode(', ', self::$hooksCalled)));
          }
        }
      }
      """
    And a file named "features/test.feature" with:
      """
      @api
      Feature: Test entity hooks
        Scenario: Entity create hooks fire without TypeError
          Given "article" content:
            | title      |
            | Test node  |
          And users:
            | name      |
            | Test user |
          And "tags" terms:
            | name      |
            | Test term |
          Then the "BeforeNodeCreate" hook should have been called
          And the "AfterNodeCreate" hook should have been called
          And the "BeforeUserCreate" hook should have been called
          And the "AfterUserCreate" hook should have been called
          And the "BeforeTermCreate" hook should have been called
          And the "AfterTermCreate" hook should have been called
      """
    When I run "behat --no-colors"
    Then it should pass

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
