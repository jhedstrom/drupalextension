@api @smoke
Feature: Screenshot
  As a developer
  I want to capture screenshots during test execution
  So that I can visually debug test failures

  Scenario: Save screenshot
    Given I am logged in as a user with the "administer site configuration" permission
    When I go to "admin"
    And I save screenshot
