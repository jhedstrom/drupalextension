Feature: DrushContext
  As a developer
  I want to run Drush commands from Behat step definitions
  So that I can test Drupal site state via the command line

  @test-drupal @api
  Scenario: Fail when drush output does not contain expected text
    Given some behat configuration
    And scenario steps tagged with "@test-drupal @api":
      """
      Given I run drush "status"
      Then drush output should contain "DOES_NOT_EXIST_xyz"
      """
    When I run behat with drupal profile
    Then it should fail

  @test-drupal @api
  Scenario: Fail when drush output does not match regular expression
    Given some behat configuration
    And scenario steps tagged with "@test-drupal @api":
      """
      Given I run drush "status"
      Then drush output should match "/^WILL_NOT_MATCH_[0-9]+$/"
      """
    When I run behat with drupal profile
    Then it should fail

  @test-drupal @api
  Scenario: Fail when drush output contains text it should not
    Given some behat configuration
    And scenario steps tagged with "@test-drupal @api":
      """
      Given I run drush "status"
      Then drush output should not contain "Drupal version"
      """
    When I run behat with drupal profile
    Then it should fail

  @test-drupal @api
  Scenario: Fail when reading drush output before running a command
    Given some behat configuration
    And scenario steps tagged with "@test-drupal @api":
      """
      Then print last drush output
      """
    When I run behat with drupal profile
    Then it should fail with an exception:
      """
      No drush output was found.
      """
