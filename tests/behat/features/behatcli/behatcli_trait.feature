@behatcli
Feature: Behat CLI context additional steps

  As Behat Drupalextension library developer
  I want to provide tools to test CLI step functionality
  So that users can verify CLI testing capabilities work correctly

  @test-blackbox
  Scenario: Assert "Then it should fail" correctly fails when the scenario fails
    Given some behat configuration
    And scenario steps tagged with "@test-blackbox":
      """
      When I use a test failing assertion step
      """
    When I run behat
    Then it should fail

  @test-blackbox
  Scenario: Assert "Then it should fail with an exception:" fails with the expected exception
    Given some behat configuration
    And scenario steps tagged with "@test-blackbox":
      """
      When I use a test passing assertion step
      Then I throw a test runtime exception with message "Intentional error"
      """
    When I run behat
    Then it should fail with an exception:
      """
      Intentional error
      """

  @test-blackbox @wip1
  Scenario: Assert "Then it should fail with an exception:" fails with the expected exception on additionally tagged scenario
    Given some behat configuration
    And scenario steps tagged with "@test-blackbox @tag1 @tag2":
      """
      When I use a test passing assertion step
      Then I throw a test runtime exception with message "Intentional error"
      """
    When I run behat
    Then it should fail with an exception:
      """
      Intentional error
      """

  @test-blackbox
  Scenario: Assert "Then it should fail with an exception:" rejects non-RuntimeException
    Given some behat configuration
    And scenario steps tagged with "@test-blackbox":
      """
      When I use a test passing assertion step
      Then I throw a test assertion exception "Exception" with message "Plain exception error"
      """
    When I run behat
    Then it should fail with:
      """
      Plain exception error
      """
    And the output should contain:
      """
      (Exception)
      """
    And the output should not contain:
      """
      (RuntimeException)
      """

  @test-blackbox
  Scenario: Assert "Then it should fail with an error:" correctly fails with the expected error message
    Given some behat configuration
    And scenario steps tagged with "@test-blackbox":
      """
      When I use a test passing assertion step
      When I use a test failing assertion step
      """
    When I run behat
    Then it should fail with an error:
      """
      This is a test failing assertion.
      """

  @test-blackbox
  Scenario: Assert "Then it should fail with an error:" correctly catches plain assertion exceptions
    Given some behat configuration
    And scenario steps tagged with "@test-blackbox":
      """
      When I use a test passing assertion step
      Then I throw a test assertion exception "Exception" with message "Plain assertion error"
      """
    When I run behat
    Then it should fail with an error:
      """
      Plain assertion error
      """

  @test-blackbox
  Scenario: Assert "Then it should fail with a :exception exception:" correctly catches custom exception
    Given some behat configuration
    And scenario steps tagged with "@test-blackbox":
      """
      When I use a test passing assertion step
      Then I throw a test assertion exception "InvalidArgumentException" with message "Custom exception message"
      """
    When I run behat
    Then it should fail with a "InvalidArgumentException" exception:
      """
      Custom exception message
      """

  @test-blackbox
  Scenario: Assert "Then the output should not contain:" passes when text is absent
    Given some behat configuration
    And scenario steps tagged with "@test-blackbox":
      """
      When I use a test passing assertion step
      """
    When I run behat
    Then it should pass
    And the output should not contain:
      """
      UNEXPECTED_OUTPUT_STRING
      """

  @test-blackbox
  Scenario: Assert "Then it should fail with an error:" rejects RuntimeException
    Given some behat configuration
    And scenario steps tagged with "@test-blackbox":
      """
      When I use a test passing assertion step
      Then I throw a test runtime exception with message "Runtime error"
      """
    When I run behat
    Then it should fail with:
      """
      Runtime error
      """
    And the output should contain:
      """
      (RuntimeException)
      """
    And the output should not contain:
      """
      (Exception)
      """
