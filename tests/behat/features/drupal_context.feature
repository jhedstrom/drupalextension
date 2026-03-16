Feature: DrupalContext coverage gaps
  As a developer
  I want comprehensive tests for DrupalContext step definitions
  So that I can verify user, node, and term operations work correctly

  @test-drupal @api
  Scenario: Assert "Given I am viewing my :type with the title :title" passes
    Given I am logged in as a user with the "administrator" role
    When I am viewing my "article" content with the title "My own article"
    Then I should see the heading "My own article"

  @test-drupal @api
  Scenario: Assert "Given I am viewing my :type with the title :title" fails for anonymous user
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
  Scenario: Assert "Given I am logged in as a user with the :role role and I have the following fields:" passes
    Given I am logged in as a user with the "authenticated user" role and I have the following fields:
      | name | TestFieldUser |
    Then I should see the link "My account"

  @test-drupal @api
  Scenario: Assert "Given I am logged in as a user with the :role role" always creates a fresh user
    Given I am logged in as a user with the "authenticated user" role
    And I remember the current user name
    When I am logged in as a user with the "authenticated user" role
    Then the current user should be different from the remembered user

  @test-drupal @api
  Scenario: Assert "Given I am an anonymous user" passes
    Given I am an anonymous user
    When I visit "/user/login"
    Then I should see the text "Log in"

  @test-drupal @api
  Scenario: Assert "Then I log out" passes
    Given I am logged in as a user with the "authenticated user" role
    When I log out
    And I visit "/user/login"
    Then I should see the text "Log in"

  @test-drupal @api
  Scenario: Assert "Given I am logged in as :name" fails for nonexistent user
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
  Scenario: Assert "Then I should see :text in the :rowText row" passes
    Given I am logged in as a user with the "administrator" role
    And "article" content:
      | title         | status |
      | Row text test | 1      |
    When I go to "admin/content"
    Then I should see "Article" in the "Row text test" row
    And I should not see the text "Nonexistent Type" in the "Row text test" row

  @test-drupal @api
  Scenario: Assert "Then I should see :text in the :rowText row" fails when row not found
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
  Scenario: Assert "Then I should see :text in the :rowText row" fails when text not in row
    Given some behat configuration
    And scenario steps tagged with "@test-drupal @api":
      """
      Given I am logged in as a user with the "administrator" role
      And "article" content:
        | title         | status |
        | Row text test | 1      |
      When I go to "admin/content"
      Then I should see "NONEXISTENT_xyz" in the "Row text test" row
      """
    When I run behat with drupal profile
    Then it should fail with an error:
      """
      Found a row containing "Row text test", but it did not contain the text "NONEXISTENT_xyz".
      """

  @test-drupal @api
  Scenario: Assert "Then I should not see :text in the :rowText row" fails when text is in row
    Given some behat configuration
    And scenario steps tagged with "@test-drupal @api":
      """
      Given I am logged in as a user with the "administrator" role
      And "article" content:
        | title         | status |
        | Row text test | 1      |
      When I go to "admin/content"
      Then I should not see "Article" in the "Row text test" row
      """
    When I run behat with drupal profile
    Then it should fail with an error:
      """
      Found a row containing "Row text test", but it contained the text "Article".
      """

  @test-drupal @api
  Scenario: Assert "Then I should see the :link in the :rowText row" passes
    Given I am logged in as a user with the "administrator" role
    And "article" content:
      | title         | status |
      | Link row test | 1      |
    When I go to "admin/content"
    Then I should see the "Edit" in the "Link row test" row

  @test-drupal @api
  Scenario: Assert "Then I should see the :link in the :rowText row" fails for missing link
    Given some behat configuration
    And scenario steps tagged with "@test-drupal @api":
      """
      Given I am logged in as a user with the "administrator" role
      And "article" content:
        | title         | status |
        | Link row test | 1      |
      When I go to "admin/content"
      Then I should see the "Nonexistent link" in the "Link row test" row
      """
    When I run behat with drupal profile
    Then it should fail with an error:
      """
      no "Nonexistent link" link
      """

  @test-drupal @api
  Scenario: Assert "Then I should not see the :link in the :rowText row" passes
    Given I am logged in as a user with the "administrator" role
    And "article" content:
      | title         | status |
      | Link row test | 1      |
    When I go to "admin/content"
    Then I should not see the "Nonexistent link" in the "Link row test" row

  @test-drupal @api
  Scenario: Assert "Then I should not see the :link in the :rowText row" fails for existing link
    Given some behat configuration
    And scenario steps tagged with "@test-drupal @api":
      """
      Given I am logged in as a user with the "administrator" role
      And "article" content:
        | title         | status |
        | Link row test | 1      |
      When I go to "admin/content"
      Then I should not see the "Edit" in the "Link row test" row
      """
    When I run behat with drupal profile
    Then it should fail with an error:
      """
      with a "Edit" link
      """

  @test-drupal @api
  Scenario: Assert "Given I click :link in the :rowText row" fails when link not in row
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

  @test-drupal @api
  Scenario: Assert "Given I press :button in the :rowText row" fails when button not in row
    Given some behat configuration
    And scenario steps tagged with "@test-drupal @api":
      """
      Given I am logged in as a user with the "administrator" role
      And "article" content:
        | title         |
        | Button target |
      When I go to "admin/content"
      Then I press "Nonexistent button" in the "Button target" row
      """
    When I run behat with drupal profile
    Then it should fail with an error:
      """
      no "Nonexistent button" button
      """
