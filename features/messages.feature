@api @d8
Feature: Ensure that messages are working properly on local installs
  In order to be sure that Drupal 8 with Big Pipe enabled can be tested
  Messages are tested against a local installation

  Scenario: Non-JS messages for anonymous
    Given I am on "/user/login"
    When I fill in "a fake user" for "Username"
    And I fill in "a fake password" for "Password"
    When I press "Log in"
    Then I should see the error message "Unrecognized username or password"

  @javascript
  Scenario: JS messages for anonymous
    Given I am on "/user/login"
    When I fill in "a fake user" for "Username"
    And I fill in "a fake password" for "Password"
    When I press "Log in"
    Then I should see the error message "Unrecognized username or password"

  Scenario: Non-JS messages for authenticated
    Given I am logged in as a user with the "access site in maintenance mode,administer site configuration" permissions
    And I am on "/admin/config/development/maintenance"
    When I check the box "maintenance_mode"
    And I press "Save configuration"
    And print last response
    Then I should see the message "The configuration options have been saved."

  @javascript
  Scenario: JS messages for authenticated
    Given I am logged in as a user with the "access site in maintenance mode,administer site configuration" permissions
    And I am on "/admin/config/development/maintenance"
    When I check the box "maintenance_mode"
    And I press "Save configuration"
    And print last response
    Then I should see the message "The configuration options have been saved."
