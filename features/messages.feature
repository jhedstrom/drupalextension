@api @d8
Feature: Ensure that messages are working properly on local installs
  In order to be sure that Drupal 8 with Big Pipe enabled can be tested
  Messages are tested against a local installation

  Scenario: Non-JS messages
    Given I am on "/user/login"
    When I fill in "a fake user" for "Username"
    And I fill in "a fake password" for "Password"
    And I press "Log in"
    Then I should see the error message "Unrecognized username or password"

  @javascript
  Scenario: JS messages
    Given I am on "/user/login"
    When I fill in "a fake user" for "Username"
    And I fill in "a fake password" for "Password"
    And I press "Log in"
    Then I should see the error message "Unrecognized username or password"
