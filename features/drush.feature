@drushTest @drush
Feature: Drush-specific steps
  In order to prove that the drush driver is working properly
  As a developer
  I need to be able to use the steps provided here

  Scenario: drush command with text matching: drush output correct status
    Given I run drush "st"
    Then drush output should contain "Drupal version"
    Then drush output should contain "Site URI"
    Then drush output should match "/.*Site\sURI\s+:.*/"
    Then drush output should contain "Database driver"
    Then drush output should contain "Successful"
    Then drush output should not contain "NonExistantWord"

  Scenario: drush command with arguments: re-enable toolbar
    Given I run drush "en" "toolbar -y"
      And I run drush "en" "toolbar -y"
    Then drush output should contain "toolbar is already enabled."

  Scenario: Create and view a node with fields using the Drush driver
    Given I am viewing an "Article":
    | title | My article with fields! |
    | body  | A placeholder           |
    Then I should see the heading "My article with fields!"
    And I should see the text "A placeholder"
