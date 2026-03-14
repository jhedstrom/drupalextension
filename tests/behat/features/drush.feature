Feature: DrushContext
  As a developer
  I want to run Drush commands from Behat step definitions
  So that I can test Drupal site state via the command line

  @api @test-drupal
  Scenario: Run a drush command
    Given I run drush "status"
    Then drush output should contain "Drupal version"

  @api @test-drupal
  Scenario: Run a drush command with arguments
    Given I run drush "config:get" "system.site name"
    Then drush output should contain "Site-Install"

  @api @test-drupal
  Scenario: Drush output should contain expected text
    Given I run drush "status"
    Then drush output should contain "PHP version"

  @api @test-drupal
  Scenario: Drush output should match a regular expression
    Given I run drush "status"
    Then drush output should match "/Drupal version\s*:\s*[0-9]+/"

  @api @test-drupal
  Scenario: Drush output should not contain unexpected text
    Given I run drush "status"
    Then drush output should not contain "DOES_NOT_EXIST_xyz"

  @api @test-drupal
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
    Then it should fail with an error:
      """
      The last drush command output did not contain 'DOES_NOT_EXIST_xyz'
      """

  @test-blackbox
  Scenario: Fail when drush output does not match regular expression
    Given some behat configuration
    And scenario steps:
      """
      Given I run drush "status"
      Then drush output should match "/^WILL_NOT_MATCH_[0-9]+$/"
      """
    When I run "behat --no-colors"
    Then it should fail with an error:
      """
      The pattern /^WILL_NOT_MATCH_[0-9]+$/ was not found anywhere in the drush output
      """

  @test-blackbox
  Scenario: Fail when drush output contains text it should not
    Given some behat configuration
    And scenario steps:
      """
      Given I run drush "status"
      Then drush output should not contain "Drupal version"
      """
    When I run "behat --no-colors"
    Then it should fail with an error:
      """
      The last drush command output did contain 'Drupal version' although it should not
      """

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
