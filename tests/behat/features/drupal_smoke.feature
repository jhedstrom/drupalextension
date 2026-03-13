@smoke @test-drupal
Feature: Drupal driver smoke test

  As a site administrator
  I want to log into the system
  So that I can access administrative functions and manage the site

  @api
  Scenario: Administrator user logs in
    Given I am logged in as a user with the "administer site configuration, access administration pages" permissions
    When I go to "admin"
    And I save screenshot

  @api @javascript
  Scenario: Administrator user logs in using a real browser
    Given I am logged in as a user with the "administer site configuration, access administration pages" permissions
    When I go to "admin"
    And I save screenshot
