@api @d8
Feature: ConfigContext
  In order to prove the Config context is working properly
  As a developer
  I need to use the step definitions of this context

  Background: User is an administrator.
    Given I am logged in as a user with the "administer site configuration" permission

  Scenario: Set the site name and check it appears on the config form.
    Given I set the configuration item "system.site" with key "name" to "Test config update"
    When  I go to "admin/config/system/site-information"
    Then  the "Site name" field should contain "Test config update"

  Scenario: Set a complex config and check it appears on the config form.
    Given I set the configuration item "system.performance" with key "css" with values:
      |key        | value |
      |preprocess | true  |
    When I go to "admin/config/development/performance"
    Then the "Aggregate CSS files" checkbox should be checked
