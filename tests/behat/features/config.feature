Feature: ConfigContext
  As a developer
  I want to manage Drupal configuration in test scenarios
  So that I can verify site settings changes are applied correctly

  Background: User is an administrator.
    Given I am logged in as a user with the "administer site configuration" permission

  @test-drupal @api
  Scenario: Assert "Given I set the configuration item :name with key :key to :value" passes
    Given I set the configuration item "system.site" with key "name" to "Test config update"
    When  I go to "admin/config/system/site-information"
    Then  the "Site name" field should contain "Test config update"

  @test-drupal @api
  Scenario: Assert "Given I set the configuration item :name with key :key with values:" passes
    Given I set the configuration item "system.performance" with key "css" with values:
      | key        | value |
      | preprocess | true  |
    When I go to "admin/config/development/performance"
    Then the "Aggregate CSS files" checkbox should be checked

  @test-drupal @api
  Scenario: Assert config is restored after scenario
    Given I set the configuration item "system.site" with key "name" to "Temporary Name"
    When I go to "admin/config/system/site-information"
    Then the "Site name" field should contain "Temporary Name"

  @test-drupal @api
  Scenario: Assert config was restored by previous scenario cleanup
    When I go to "admin/config/system/site-information"
    Then the "Site name" field should not contain "Temporary Name"

  @test-drupal @api
  Scenario: Assert config backup uses original DB value when overridden in settings.php
    Given I set the configuration item "system.site" with key "name" to "Temporary Override Test"
    When I go to "admin/config/system/site-information"
    Then the "Site name" field should contain "Temporary Override Test"

  @test-drupal @api
  Scenario: Assert config was restored to original DB value not the settings.php override
    Then the original configuration item "system.site" with key "name" should be "Drush Site-Install"
