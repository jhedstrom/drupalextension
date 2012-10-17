Feature: Test DrupalContext
  In order to prove the Drupal context is working properly
  As a developer
  I need to use the step definitions of this context

  @api
  Scenario: Test the functionality of drush aliases
    Given I am logged in as a user with the "authenticated user" role
    When I click "Log out"
    Then I should be logged out

  @api
  Scenario: Test the ability to target links within table rows
    Given I am logged in as a user with the "administrator" role
    When I am at "admin/structure/types"
    And I click "manage fields" in the "Article" row
    Then I should be on "admin/structure/types/manage/article/fields"
    And I should see text matching "Add new field"
