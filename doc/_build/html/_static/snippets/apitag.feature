Feature: Drush alias
  In order to demonstrate the Drush driver
  As a trainer
  I need to show how to tag scenarios 

  Scenario: Untagged scenario uses blackbox driver and fails
    Given I am logged in as a user with the "authenticated user" role
    When I click "My account"
    Then I should see the heading "History"

  @api
  Scenario: Tagged scenario uses Drush driver and succeeds
    Given I am logged in as a user with the "authenticated user" role
    When I click "My account"
    Then I should see the heading "History"
