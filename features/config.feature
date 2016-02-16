@api
Feature: ConfigContext
  In order to prove the Config context is working properly
  As a developer
  I need to use the step definitions of this context

  @d8
  Scenario: Set the site name and check it appears on the config form.
    Given I set the configuration item "system.site" with key "name" to "Test config update"
    When  I go to "admin/config/system/site-information"
    Then  the "Site name" field should contain "Test config update"
