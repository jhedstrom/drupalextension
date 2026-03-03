@api
Feature: Ensure that messages are working properly on local installs
  As a developer
  I want to verify Drupal status messages in tests
  So that I can assert error and confirmation messages appear correctly

  Scenario: Non-JS messages
    Given I am on "/user/login"
    When I fill in "a fake user" for "Username"
    And I fill in "a fake password" for "Password"
    And I press "Log in"
    Then I should see the error message "Unrecognized username or password"
