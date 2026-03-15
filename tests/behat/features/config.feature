Feature: ConfigContext
  As a developer
  I want to manage Drupal configuration in test scenarios
  So that I can verify site settings changes are applied correctly

  Background: User is an administrator.
    Given I am logged in as a user with the "administer site configuration" permission

  @test-drupal @api
  Scenario: Set the site name and check it appears on the config form.
    Given I set the configuration item "system.site" with key "name" to "Test config update"
    When  I go to "admin/config/system/site-information"
    Then  the "Site name" field should contain "Test config update"

  @test-drupal @api
  Scenario: Set a complex config and check it appears on the config form.
    Given I set the configuration item "system.performance" with key "css" with values:
      | key        | value |
      | preprocess | true  |
    When I go to "admin/config/development/performance"
    Then the "Aggregate CSS files" checkbox should be checked

  @test-drupal @api
  Scenario: Config is restored after scenario
    Given I set the configuration item "system.site" with key "name" to "Temporary Name"
    When I go to "admin/config/system/site-information"
    Then the "Site name" field should contain "Temporary Name"

  @test-drupal @api
  Scenario: Verify config was restored by previous scenario cleanup
    When I go to "admin/config/system/site-information"
    Then the "Site name" field should not contain "Temporary Name"
