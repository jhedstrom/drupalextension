Feature: DrushContext
  As a developer
  I want to run Drush commands from Behat step definitions
  So that I can test Drupal site state via the command line

  @test-drupal @api
  Scenario: Assert "Given I run drush :command" passes
    Given I run drush "status"
    Then the drush output should contain "Drupal version"

  @test-drupal @api
  Scenario: Assert "Given I run drush :command :arguments" passes with a flag-only arguments string
    Given I run drush "pm:list" "--status=enabled"
    Then the drush output should contain "Enabled"

  @test-drupal @api
  Scenario: Assert "Given I run drush :command :arguments" passes with positional arguments and options combined
    Given I run drush "config:get" "system.site uuid --format=string"
    Then the drush output should match "/[a-f0-9-]{30,}/"
    And the drush output should not contain "system.site"

  @test-drupal @api
  Scenario: Assert "Then the drush output should contain :output" passes
    Given I run drush "status"
    Then the drush output should contain "Drupal version"

  @test-drupal @api
  Scenario: Assert "Then the drush output should contain :output" fails when text not found
    Given some behat configuration
    And scenario steps tagged with "@test-drupal @api":
      """
      Given I run drush "status"
      Then the drush output should contain "DOES_NOT_EXIST_xyz"
      """
    When I run behat with drupal profile
    Then it should fail with an error:
      """
      The last drush command output did not contain 'DOES_NOT_EXIST_xyz'.
      """

  @test-drupal @api
  Scenario: Assert "Then the drush output should match :regex" passes
    Given I run drush "status"
    Then the drush output should match "/Drupal version/"

  @test-drupal @api
  Scenario: Assert "Then the drush output should match :regex" fails when pattern not found
    Given some behat configuration
    And scenario steps tagged with "@test-drupal @api":
      """
      Given I run drush "status"
      Then the drush output should match "/^WILL_NOT_MATCH_[0-9]+$/"
      """
    When I run behat with drupal profile
    Then it should fail with an error:
      """
      The pattern /^WILL_NOT_MATCH_[0-9]+$/ was not found anywhere in the drush output.
      """

  @test-drupal @api
  Scenario: Assert "Then the drush output should not contain :output" passes
    Given I run drush "status"
    Then the drush output should not contain "DOES_NOT_EXIST_xyz"

  @test-drupal @api
  Scenario: Assert "Then the drush output should not contain :output" fails when text is present
    Given some behat configuration
    And scenario steps tagged with "@test-drupal @api":
      """
      Given I run drush "status"
      Then the drush output should not contain "Drupal version"
      """
    When I run behat with drupal profile
    Then it should fail with an error:
      """
      The last drush command output did contain 'Drupal version' although it should not.
      """

  @test-drupal @api
  Scenario: Assert "When I print the last drush output" passes
    Given I run drush "status"
    When I print the last drush output

  @test-drupal @api
  Scenario: Assert "When I print the last drush output" fails when no command has been run
    Given some behat configuration
    And scenario steps tagged with "@test-drupal @api":
      """
      When I print the last drush output
      """
    When I run behat with drupal profile
    Then it should fail with an exception:
      """
      No drush output was found.
      """
