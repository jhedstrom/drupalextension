Feature: Drush-specific steps
  In order to prove that the drush driver is working properly
  As a developer
  I need to be able to use the steps provided here

  @drush
  Scenario: drush command with text matching: drush output correct status
    Given I run drush "st"
    Then drush output should contain "Drupal version"
    Then drush output should contain "Site URI"
    Then drush output should contain "Database driver"
    Then drush output should contain "Successful"
    Then drush output should not contain "NonExistantWord"

  @drush
  Scenario: drush command with arguments: re-enable toolbar
    Given I run drush "en" "toolbar -y"
      And I run drush "en" "toolbar -y"
    Then drush output should contain "toolbar is already enabled."

  @drush
  Scenario: try text matching with no drush command return an error.
    Then drush output should contain "something"
