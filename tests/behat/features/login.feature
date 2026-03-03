@api @smoke
Feature: Screenshot
  In order to capture test state for debugging
  As a developer
  I need to be able to save screenshots during tests

  Scenario: Save screenshot
    Given I am logged in as a user with the "administer site configuration" permission
    When I go to "admin"
    And I save screenshot
