Feature: DrushContext
  As a developer
  I want to run Drush commands from Behat step definitions
  So that I can test Drupal site state via the command line

  # Skipped: DrushDriver misdetects legacy drush on PHP 8.4 with --prefer-lowest
  # due to unfixed deprecation notices in consolidation/* 4.x packages.
  @api @test-drupal @skipped
  Scenario: Run a drush command
    Given I run drush "status"
    Then drush output should contain "Drupal version"

  @api @test-drupal @skipped
  Scenario: Run a drush command with arguments
    Given I run drush "config:get" "system.site name"
    Then drush output should contain "Site-Install"

  @api @test-drupal @skipped
  Scenario: Drush output should contain expected text
    Given I run drush "status"
    Then drush output should contain "PHP version"

  @api @test-drupal @skipped
  Scenario: Drush output should match a regular expression
    Given I run drush "status"
    Then drush output should match "/Drupal version\s*:\s*[0-9]+/"

  @api @test-drupal @skipped
  Scenario: Drush output should not contain unexpected text
    Given I run drush "status"
    Then drush output should not contain "DOES_NOT_EXIST_xyz"

  @api @test-drupal @skipped
  Scenario: Print last drush output
    Given I run drush "status"
    Then print last drush output

  @test-blackbox
  Scenario: Fail when drush output does not contain expected text
    Given some behat configuration
    And scenario steps:
      """
      Given I run drush "status"
      Then drush output should contain "DOES_NOT_EXIST_xyz"
      """
    When I run "behat --no-colors"
    Then it should fail

  @test-blackbox
  Scenario: Fail when drush output does not match regular expression
    Given some behat configuration
    And scenario steps:
      """
      Given I run drush "status"
      Then drush output should match "/^WILL_NOT_MATCH_[0-9]+$/"
      """
    When I run "behat --no-colors"
    Then it should fail

  @test-blackbox
  Scenario: Fail when drush output contains text it should not
    Given some behat configuration
    And scenario steps:
      """
      Given I run drush "status"
      Then drush output should not contain "Drupal version"
      """
    When I run "behat --no-colors"
    Then it should fail

  @test-blackbox
  Scenario: Fail when reading drush output before running a command
    Given some behat configuration
    And scenario steps:
      """
      Then print last drush output
      """
    When I run "behat --no-colors"
    Then it should fail with an exception:
      """
      No drush output was found.
      """
