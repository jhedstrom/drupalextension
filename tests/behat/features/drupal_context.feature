Feature: DrupalContext coverage gaps
  As a developer
  I want comprehensive tests for DrupalContext step definitions
  So that I can verify user, node, and term operations work correctly

  @api @test-drupal
  Scenario: Create and view own content
    Given I am logged in as a user with the "administrator" role
    When I am viewing my "article" content with the title "My own article"
    Then I should see the heading "My own article"

  @api @test-drupal
  Scenario: Login with role and custom fields
    Given I am logged in as a user with the "authenticated user" role and I have the following fields:
      | name | TestFieldUser |
    Then I should see the link "My account"

  @api @test-drupal
  Scenario: See and not see text in a table row
    Given I am logged in as a user with the "administrator" role
    And "article" content:
      | title         | status |
      | Row text test | 1      |
    When I go to "admin/content"
    Then I should see "Article" in the "Row text test" row
    And I should not see the text "Nonexistent Type" in the "Row text test" row

  @api @test-drupal
  Scenario: Verify anonymous user state
    Given I am an anonymous user
    When I visit "/user/login"
    Then I should see the text "Log in"

  @api @test-drupal
  Scenario: Log out resets authentication
    Given I am logged in as a user with the "authenticated user" role
    When I log out
    And I visit "/user/login"
    Then I should see the text "Log in"

  @api @test-drupal
  Scenario: See link in a table row
    Given I am logged in as a user with the "administrator" role
    And "article" content:
      | title          | status |
      | Button row test | 1     |
    When I go to "admin/content"
    Then I should see the "Edit" in the "Button row test" row

  @api @test-drupal
  Scenario: Not see text in a table row that is present should pass
    Given I am logged in as a user with the "administrator" role
    And "article" content:
      | title          | status |
      | Absent text row | 1     |
    When I go to "admin/content"
    Then I should not see the text "NONEXISTENT_STRING_xyz" in the "Absent text row" row

  @api @test-drupal
  Scenario: Create content without viewing it
    Given I am logged in as a user with the "administrator" role
    And an "article" with the title "Created not viewed"
    When I go to "admin/content"
    Then I should see "Created not viewed"

  @api @test-drupal
  Scenario: Create a standalone taxonomy term
    Given I am logged in as a user with the "administrator" role
    And a "tags" term with the name "Standalone term"
    When I go to "admin/structure/taxonomy/manage/tags/overview"
    Then I should see "Standalone term"

  @api @test-drupal
  Scenario: Press button in a table row
    Given I am at "/behat-test/table-button"
    Then I press "Edit" in the "First row" row

  @test-blackbox
  Scenario: Fail when text is present in row but should not be
    Given some behat configuration
    And scenario steps:
      """
      Given I am logged in as a user with the "administrator" role
      And "article" content:
        | title           | status |
        | Text present row | 1     |
      When I go to "admin/content"
      Then I should not see the text "Article" in the "Text present row" row
      """
    When I run "behat --no-colors"
    Then it should fail

  @test-blackbox
  Scenario: Fail when link not visible in table row
    Given some behat configuration
    And scenario steps:
      """
      Given I am logged in as a user with the "administrator" role
      And "article" content:
        | title           | status |
        | Link visible row | 1     |
      When I go to "admin/content"
      Then I should see the "Nonexistent link" in the "Link visible row" row
      """
    When I run "behat --no-colors"
    Then it should fail with an error:
      """
      no "Nonexistent link" link
      """

  @test-blackbox
  Scenario: Fail when pressing nonexistent button in table row
    Given some behat configuration
    And scenario steps:
      """
      Given I am logged in as a user with the "administrator" role
      And "article" content:
        | title              | status |
        | Button missing row | 1      |
      When I go to "admin/content"
      Then I press "Nonexistent button" in the "Button missing row" row
      """
    When I run "behat --no-colors"
    Then it should fail with an error:
      """
      no "Nonexistent button" button
      """

  @test-blackbox
  Scenario: Fail when creating content with non-existent type
    Given some behat configuration
    And scenario steps:
      """
      Given I am viewing a "nonexistent_type" with the title "Bad type"
      """
    When I run "behat --no-colors"
    Then it should fail

  @test-blackbox
  Scenario: Fail when viewing own content as anonymous
    Given some behat configuration
    And scenario steps:
      """
      Given I am viewing my "article" content with the title "Anon viewing"
      """
    When I run "behat --no-colors"
    Then it should fail with an error:
      """
      There is no current logged in user to create a node for.
      """

  @test-blackbox
  Scenario: Fail when editing content without access
    Given some behat configuration
    And scenario steps:
      """
      Given I am logged in as a user with the "authenticated user" role
      Then I should be able to edit an "article"
      """
    When I run "behat --no-colors"
    Then it should fail

  @test-blackbox
  Scenario: Fail when creating content for anonymous user
    Given some behat configuration
    And scenario steps:
      """
      Given I am viewing my "article" content with the title "Anon content"
      """
    When I run "behat --no-colors"
    Then it should fail with an error:
      """
      There is no current logged in user to create a node for.
      """

  @test-blackbox
  Scenario: Fail when logging in as nonexistent user
    Given some behat configuration
    And scenario steps:
      """
      Given users:
        | name      | status |
        | Test user | 1      |
      When I am logged in as "Nonexistent user"
      """
    When I run "behat --no-colors"
    Then it should fail with a "InvalidArgumentException" exception:
      """
      No user with Nonexistent user name is registered with the driver.
      """

  @test-blackbox
  Scenario: Fail when finding text in nonexistent table row
    Given some behat configuration
    And scenario steps:
      """
      Given I am logged in as a user with the "administrator" role
      When I go to "admin/content"
      Then I should see "something" in the "NONEXISTENT_ROW" row
      """
    When I run "behat --no-colors"
    Then it should fail with an error:
      """
      Failed to find a row containing "NONEXISTENT_ROW"
      """

  @test-blackbox
  Scenario: Fail when clicking nonexistent link in table row
    Given some behat configuration
    And scenario steps:
      """
      Given I am logged in as a user with the "administrator" role
      And "article" content:
        | title        |
        | Click target |
      When I go to "admin/content"
      Then I click "Nonexistent link" in the "Click target" row
      """
    When I run "behat --no-colors"
    Then it should fail with an error:
      """
      no "Nonexistent link" link
      """

  @test-blackbox
  Scenario: Fail when creating content with multiple rows of invalid type
    Given some behat configuration
    And scenario steps:
      """
      Given "nonexistent_type_xyz" content:
        | title      |
        | Bad content |
      """
    When I run "behat --no-colors"
    Then it should fail
