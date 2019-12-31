@api @d8
Feature: Backend login/logout
  In order to prove that backend authentication is working
  As a developer
  I need to utilize the backend login functionality of the authentication manager

  Scenario: Log a user in on the backend
    Given I am logged in as a user with the "authenticated user" role
    Then I should be logged in on the backend

  Scenario: Logout on the backend
    Given I am logged in as a user with the "authenticated user" role
    And I am logged in on the backend
    When I log out
    Then I should be logged out on the backend
