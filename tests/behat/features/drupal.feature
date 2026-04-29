Feature: DrupalContext coverage gaps
  As a developer
  I want comprehensive tests for DrupalContext step definitions
  So that I can verify user, node, and term operations work correctly

  @test-drupal @api
  Scenario: Assert head content and Drupal settings JSON are not visible in page text
    Given I am logged in as a user with the "authenticated user" role
    When I am on the homepage
    Then I should see the heading "Welcome!"
    And I should not see "permissionsHash"

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
    Then it should fail with an exception:
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
  Scenario: Assert "When I log out" passes
    Given I am logged in as a user with the "authenticated user" role
    When I log out
    And I visit "/user/login"
    Then I should see the text "Log in"

  @test-drupal @api
  Scenario: Assert login state is reset between scenarios
    Given some behat configuration
    And a file named "features/stub.feature" with:
      """
      Feature: Login state across scenarios
        @test-drupal @api
        Scenario: One
          Given I am logged in as a user with the "authenticated" role
          And I delete the current user from the database

        @test-drupal @api
        Scenario: Two
          Then I should be logged out on the backend
      """
    When I run behat with drupal profile
    Then it should pass

  @test-drupal @api
  Scenario: Assert "Given I am logged in as :name" fails for nonexistent user
    Given some behat configuration
    And scenario steps tagged with "@test-drupal @api":
      """
      Given the following users:
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
  Scenario: Assert "Then I should see the text :text in the :rowText row" passes
    Given I am logged in as a user with the "administrator" role
    And the following "article" content:
      | title         | status |
      | Row text test | 1      |
    When I go to "admin/content"
    Then I should see the text "Article" in the "Row text test" row
    And I should not see the text "Nonexistent Type" in the "Row text test" row

  @test-drupal @api
  Scenario: Assert "Then I should see the text :text in the :rowText row" fails when row not found
    Given some behat configuration
    And scenario steps tagged with "@test-drupal @api":
      """
      Given I am logged in as a user with the "administrator" role
      When I go to "admin/content"
      Then I should see the text "something" in the "NONEXISTENT_ROW" row
      """
    When I run behat with drupal profile
    Then it should fail with an error:
      """
      Row with text "NONEXISTENT_ROW" not found.
      """

  @test-drupal @api
  Scenario: Assert "Then I should see the text :text in the :rowText row" fails when text not in row
    Given some behat configuration
    And scenario steps tagged with "@test-drupal @api":
      """
      Given I am logged in as a user with the "administrator" role
      And the following "article" content:
        | title         | status |
        | Row text test | 1      |
      When I go to "admin/content"
      Then I should see the text "NONEXISTENT_xyz" in the "Row text test" row
      """
    When I run behat with drupal profile
    Then it should fail with an error:
      """
      Found a row containing "Row text test", but it did not contain the text "NONEXISTENT_xyz".
      """

  @test-drupal @api
  Scenario: Assert "Then I should not see the text :text in the :rowText row" fails when text is in row
    Given some behat configuration
    And scenario steps tagged with "@test-drupal @api":
      """
      Given I am logged in as a user with the "administrator" role
      And the following "article" content:
        | title         | status |
        | Row text test | 1      |
      When I go to "admin/content"
      Then I should not see the text "Article" in the "Row text test" row
      """
    When I run behat with drupal profile
    Then it should fail with an error:
      """
      Found a row containing "Row text test", but it contained the text "Article".
      """

  @test-drupal @api
  Scenario: Assert "Then I should see the :link in the :rowText row" passes
    Given I am logged in as a user with the "administrator" role
    And the following "article" content:
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
      And the following "article" content:
        | title         | status |
        | Link row test | 1      |
      When I go to "admin/content"
      Then I should see the "Nonexistent link" in the "Link row test" row
      """
    When I run behat with drupal profile
    Then it should fail with an error:
      """
      Link in the "Link row test" row with id|title|alt|text "Nonexistent link" not found.
      """

  @test-drupal @api
  Scenario: Assert "Then I should not see the :link in the :rowText row" passes
    Given I am logged in as a user with the "administrator" role
    And the following "article" content:
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
      And the following "article" content:
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
      And the following "article" content:
        | title        |
        | Click target |
      When I go to "admin/content"
      Then I click "Nonexistent link" in the "Click target" row
      """
    When I run behat with drupal profile
    Then it should fail with an error:
      """
      Link in the "Click target" row with id|title|alt|text "Nonexistent link" not found.
      """

  @test-drupal @api
  Scenario: Assert "Given I press :button in the :rowText row" fails when button not in row
    Given some behat configuration
    And scenario steps tagged with "@test-drupal @api":
      """
      Given I am logged in as a user with the "administrator" role
      And the following "article" content:
        | title         |
        | Button target |
      When I go to "admin/content"
      Then I press "Nonexistent button" in the "Button target" row
      """
    When I run behat with drupal profile
    Then it should fail with an error:
      """
      Button in the "Button target" row with id|name|title|alt|value "Nonexistent button" not found.
      """

  @test-drupal @api
  Scenario: Assert "Given the following :type content:" fails for orphaned multicolumn continuation
    Given some behat configuration
    And scenario steps tagged with "@test-drupal @api":
      """
      Given the following "article" content:
        | :orphan |
        | value   |
      """
    When I run behat with drupal profile
    Then it should fail with an exception:
      """
      Field name missing for :orphan
      """

  @test-drupal @api
  Scenario: Assert "Given the following :type content:" fails for non-existent field
    Given some behat configuration
    And scenario steps tagged with "@test-drupal @api":
      """
      Given the following "article" content:
        | title | field_does_not_exist |
        | test  | some value           |
      """
    When I run behat with drupal profile
    Then it should fail with an exception:
      """
      Field "field_does_not_exist" does not exist on entity type "node".
      """

  # The 3.x DrupalDriver tightened its field-handler bundle check and
  # now rejects computed base fields (such as 'moderation_state' which
  # has no field storage definition). Re-enable this scenario once the
  # driver is updated to skip computed fields gracefully.
  @test-drupal @api @skipped
  Scenario: Assert "Given the following :type content:" passes for moderation_state field
    Given some behat configuration
    And scenario steps tagged with "@test-drupal @api":
      """
      Given the following "article" content:
        | title                  | moderation_state |
        | Moderated test content | draft            |
      """
    When I run behat with drupal profile
    Then it should pass

  @test-drupal @api
  Scenario: Assert "Given the following :type content:" passes for author property
    Given some behat configuration
    And scenario steps tagged with "@test-drupal @api":
      """
      Given the following users:
        | name     | mail            | status |
        | Joe User | joe@example.com | 1      |
      And the following "article" content:
        | title          | author   | status |
        | Article by Joe | Joe User | 1      |
      """
    When I run behat with drupal profile
    Then it should pass
