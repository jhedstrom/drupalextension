@smoke
Feature: Step definition inheritance with attributes and annotations

  Tests for step definition resolution when a child context class extends
  a parent that uses PHP 8 attributes for step definitions.

  Covers the following override scenarios:
  - Child overrides with attribute only — fails (redundant with parent).
  - Child overrides with docblock annotation only — fails (redundant with parent).
  - Child overrides with both attribute and annotation — fails (redundant).
  - Child overrides method without any step definition — parent step still found.
  - Child does not override (inherits parent method) — passes.

  Background:
    Given a file named "features/bootstrap/ParentContext.php" with:
      """
      <?php

      use Behat\Behat\Context\Context;
      use Behat\Step\Given;

      class ParentContext implements Context {

          #[Given('I perform the test action')]
          public function iPerformTheTestAction(): void {
          }

      }
      """

  @test-blackbox
  Scenario: Assert child with attribute-only override fails for redundant step
    Given a file named "features/bootstrap/ChildContext.php" with:
      """
      <?php

      use Behat\Step\Given;

      class ChildContext extends ParentContext {

          #[Given('I perform the test action')]
          public function iPerformTheTestAction(): void {
          }

      }
      """
    And a file named "behat.yml" with:
      """
      default:
        suites:
          default:
            contexts:
              - ChildContext
      """
    And a file named "features/test.feature" with:
      """
      Feature: Test
        Scenario: Test
          Given I perform the test action
      """
    When I run "behat --no-colors"
    Then it should fail with:
      """
      Step "I perform the test action" is already defined in
      """

  @test-blackbox
  Scenario: Assert child with annotation-only override fails for redundant step
    Given a file named "features/bootstrap/ChildContext.php" with:
      """
      <?php

      class ChildContext extends ParentContext {

          /**
           * @Given I perform the test action
           */
          public function iPerformTheTestAction(): void {
          }

      }
      """
    And a file named "behat.yml" with:
      """
      default:
        suites:
          default:
            contexts:
              - ChildContext
      """
    And a file named "features/test.feature" with:
      """
      Feature: Test
        Scenario: Test
          Given I perform the test action
      """
    When I run "behat --no-colors"
    Then it should fail with:
      """
      Step "I perform the test action" is already defined in
      """

  @test-blackbox
  Scenario: Assert child with both attribute and annotation fails for redundant step
    Given a file named "features/bootstrap/ChildContext.php" with:
      """
      <?php

      use Behat\Step\Given;

      class ChildContext extends ParentContext {

          /**
           * @Given I perform the test action
           */
          #[Given('I perform the test action')]
          public function iPerformTheTestAction(): void {
          }

      }
      """
    And a file named "behat.yml" with:
      """
      default:
        suites:
          default:
            contexts:
              - ChildContext
      """
    And a file named "features/test.feature" with:
      """
      Feature: Test
        Scenario: Test
          Given I perform the test action
      """
    When I run "behat --no-colors"
    Then it should fail with:
      """
      Step "I perform the test action" is already defined in
      """

  @test-blackbox
  Scenario: Assert child override without step definition inherits parent step
    Given a file named "features/bootstrap/ChildContext.php" with:
      """
      <?php

      class ChildContext extends ParentContext {

          public function iPerformTheTestAction(): void {
          }

      }
      """
    And a file named "behat.yml" with:
      """
      default:
        suites:
          default:
            contexts:
              - ChildContext
      """
    And a file named "features/test.feature" with:
      """
      Feature: Test
        Scenario: Test
          Given I perform the test action
      """
    When I run "behat --no-colors"
    Then it should pass

  @test-blackbox
  Scenario: Assert child override with inheritdoc inherits parent step
    Given a file named "features/bootstrap/ChildContext.php" with:
      """
      <?php

      class ChildContext extends ParentContext {

          /**
           * {@inheritdoc}
           */
          public function iPerformTheTestAction(): void {
          }

      }
      """
    And a file named "behat.yml" with:
      """
      default:
        suites:
          default:
            contexts:
              - ChildContext
      """
    And a file named "features/test.feature" with:
      """
      Feature: Test
        Scenario: Test
          Given I perform the test action
      """
    When I run "behat --no-colors"
    Then it should pass

  @test-blackbox
  Scenario: Assert inherited step from parent attribute passes without override
    Given a file named "features/bootstrap/ChildContext.php" with:
      """
      <?php

      class ChildContext extends ParentContext {

      }
      """
    And a file named "behat.yml" with:
      """
      default:
        suites:
          default:
            contexts:
              - ChildContext
      """
    And a file named "features/test.feature" with:
      """
      Feature: Test
        Scenario: Test
          Given I perform the test action
      """
    When I run "behat --no-colors"
    Then it should pass
