@api @test-drupal
Feature: Backend login/logout
  As a developer
  I want to log in and log out via the backend driver
  So that I can test authenticated functionality without a browser session

  Scenario: Log a user in on the backend
    Given I am logged in as a user with the "authenticated user" role
    Then I should be logged in on the backend

  Scenario: Logout on the backend via fast logout
    Given I am logged in as a user with the "authenticated user" role
    And I should be logged in on the backend
    When I log out
    Then I should be logged out on the backend

  Scenario: Logout on the backend via url
    Given I am logged in as a user with the "authenticated user" role
    And I should be logged in on the backend
    When I log out via the logout url
    Then I should be logged out on the backend
