Feature: Login field
  As a developer
  I want to configure which user property is submitted as the login value
  So that I can test sites that authenticate by email instead of username

  @test-drupal @api
  Scenario: Assert default "login_field" submits the user's name
    Given I am logged in as a user with the "authenticated user" role
    Then I should see the link "My account"

  @test-drupal @api
  Scenario: Assert "login_field" set to "mail" submits the user's email
    Given some behat configuration
    And scenario steps tagged with "@test-drupal @api":
      """
      Given the following users:
        | name      | mail                  | pass   | status |
        | Test user | test-user@example.com | secret | 1      |
      When I am logged in as "Test user"
      Then I should see the link "Log out"
      """
    When I run behat with drupal profile and "login_field" set to "mail"
    Then it should pass

  @test-drupal @api
  Scenario: Assert "login_field" rejects unknown user properties
    Given some behat configuration
    And scenario steps tagged with "@test-drupal @api":
      """
      Given the following users:
        | name      | mail                  | pass   | status |
        | Test user | test-user@example.com | secret | 1      |
      When I am logged in as "Test user"
      """
    When I run behat with drupal profile and "login_field" set to "nonexistent_property"
    Then it should fail with an error:
      """
      Unable to determine if logged in
      """
