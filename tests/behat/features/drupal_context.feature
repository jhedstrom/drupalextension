Feature: DrupalContext coverage gaps
  As a developer
  I want comprehensive tests for DrupalContext step definitions
  So that I can verify user, node, and term operations work correctly

  @test-drupal @api
  Scenario: Create and view own content
    Given I am logged in as a user with the "administrator" role
    When I am viewing my "article" content with the title "My own article"
    Then I should see the heading "My own article"

  @test-drupal @api
  Scenario: Login with role and custom fields
    Given I am logged in as a user with the "authenticated user" role and I have the following fields:
      | name | TestFieldUser |
    Then I should see the link "My account"

  @test-drupal @api
  Scenario: See and not see text in a table row
    Given I am logged in as a user with the "administrator" role
    And "article" content:
      | title         | status |
      | Row text test | 1      |
    When I go to "admin/content"
    Then I should see "Article" in the "Row text test" row
    And I should not see the text "Nonexistent Type" in the "Row text test" row

  @test-drupal @api
  Scenario: Verify anonymous user state
    Given I am an anonymous user
    When I visit "/user/login"
    Then I should see the text "Log in"

  @test-drupal @api
  Scenario: Log out resets authentication
    Given I am logged in as a user with the "authenticated user" role
    When I log out
    And I visit "/user/login"
    Then I should see the text "Log in"

  @test-drupal @api
  Scenario: Fail when creating content for anonymous user
    Given some behat configuration
    And scenario steps tagged with "@test-drupal @api":
      """
      Given I am viewing my "article" content with the title "Anon content"
      """
    When I run behat with drupal profile
    Then it should fail with an error:
      """
      There is no current logged in user to create a node for.
      """

  @test-drupal @api
  Scenario: Fail when logging in as nonexistent user
    Given some behat configuration
    And scenario steps tagged with "@test-drupal @api":
      """
      Given users:
        | name      | status |
        | Test user | 1      |
      When I am logged in as "Nonexistent user"
      """
    When I run behat with drupal profile
    Then it should fail with a "InvalidArgumentException" exception:
      """
      No user with Nonexistent user name is registered with the driver.
      """

  @test-drupal @api
  Scenario: Fail when finding text in nonexistent table row
    Given some behat configuration
    And scenario steps tagged with "@test-drupal @api":
      """
      Given I am logged in as a user with the "administrator" role
      When I go to "admin/content"
      Then I should see "something" in the "NONEXISTENT_ROW" row
      """
    When I run behat with drupal profile
    Then it should fail with an error:
      """
      Failed to find a row containing "NONEXISTENT_ROW"
      """

  @test-drupal @api
  Scenario: Fail when clicking nonexistent link in table row
    Given some behat configuration
    And scenario steps tagged with "@test-drupal @api":
      """
      Given I am logged in as a user with the "administrator" role
      And "article" content:
        | title        |
        | Click target |
      When I go to "admin/content"
      Then I click "Nonexistent link" in the "Click target" row
      """
    When I run behat with drupal profile
    Then it should fail with an error:
      """
      no "Nonexistent link" link
      """
