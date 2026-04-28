Feature: DrupalContext general testing
  As a developer
  I want to create and manage Drupal entities through the API driver
  So that I can test content, users, and taxonomy without browser interaction

  # These scenarios assume a "standard" install of Drupal.

  @test-drupal @api
  Scenario: Create and log in as a user
    Given I am logged in as a user with the "authenticated user" role
    When I click "My account"
    Then I should see the text "Member for"

  @test-drupal @api
  Scenario: Clear cache
    Given the cache has been cleared
    When I am on the homepage
    Then I should get a "200" HTTP response

  @test-drupal @api
  Scenario: Create a node
    Given I am logged in as a user with the "administrator" role
    When I am viewing an "article" with the title "My article"
    Then I should see the heading "My article"

  @test-drupal @api
  Scenario: Run cron
    Given I am logged in as a user with the "administrator" role
    When I run cron
    And am on "admin/reports/dblog"
    Then I should see the link "Cron run completed"

  @test-drupal @api
  Scenario: Assert "I run cron" uses fresh request time
    Given I am logged in as a user with the "administrator" role
    And the request time is 60 seconds in the past
    When I run cron
    Then the cron request time drift should be less than 5 seconds

  @test-drupal @api
  Scenario: Create many nodes
    Given "page" content:
      | title    |
      | Page one |
      | Page two |
    And "article" content:
      | title          |
      | First article  |
      | Second article |
    And I am logged in as a user with the "administrator" role
    When I go to "admin/content"
    Then I should see "Page one"
    And I should see "Page two"
    And I should see "First article"
    And I should see "Second article"

  @test-drupal @api
  Scenario: Create nodes with fields
    Given "article" content:
      | title                     | promote | body             |
      | First article with fields | 1       | PLACEHOLDER BODY |
    And I am logged in as a user with the "authenticated user" role
    When I am on the homepage
    And follow "First article with fields"
    Then I should see the text "PLACEHOLDER BODY"

  @test-drupal @api
  Scenario: Create and view a node with fields
    Given I am viewing an "article":
      | title | My article with fields! |
      | body  | A placeholder           |
    Then I should see the heading "My article with fields!"
    And I should see the text "A placeholder"

  @test-drupal @api
  Scenario: Create users
    Given users:
      | name     | mail            | status |
      | Joe User | joe@example.com | 1      |
    And I am logged in as a user with the "administrator" role
    When I visit "admin/people"
    Then I should see the link "Joe User"

  @test-drupal @api
  Scenario: Login as a user created during this scenario
    Given users:
      | name      | status |
      | Test user | 1      |
    When I am logged in as "Test user"
    Then I should see the link "Log out"

  @test-drupal @api
  Scenario: Create a term
    Given I am logged in as a user with the "administrator" role
    When I am viewing a "tags" term with the name "My tag"
    Then I should see the heading "My tag"

  @test-drupal @api
  Scenario: Create many taxonomy terms
    Given "tags" terms:
      | name    |
      | Tag one |
      | Tag two |
    And I am logged in as a user with the "administrator" role
    When I go to "admin/structure/taxonomy/manage/tags/overview"
    Then I should see "Tag one"
    And I should see "Tag two"

  @test-drupal @api
  Scenario: Create taxonomy terms with parent hierarchy
    Given "tags" terms:
      | name          | parent        |
      | Root term     |               |
      | Child term    | Root term     |
      | Grandchild    | Child term    |
      | Great-grandch | Grandchild    |
    Then the "tags" term "Child term" should have parent "Root term"
    And the "tags" term "Grandchild" should have parent "Child term"
    And the "tags" term "Great-grandch" should have parent "Grandchild"

  @test-drupal @api
  Scenario: Assert "Given :vocabulary terms:" fails for non-existent parent term
    Given some behat configuration
    And scenario steps tagged with "@test-drupal @api":
      """
      Given "tags" terms:
        | name   | parent              |
        | Orphan | NonExistentParent99 |
      """
    When I run behat with drupal profile
    Then it should fail with a "InvalidArgumentException" exception:
      """
      Cannot create term because parent term 'NonExistentParent99' does not exist in vocabulary 'tags'.
      """

  @test-drupal @api
  Scenario: Create terms using vocabulary title rather than machine name
    Given "Tags" terms:
      | name    |
      | Tag one |
      | Tag two |
    And I am logged in as a user with the "administrator" role
    When I go to "admin/structure/taxonomy/manage/tags/overview"
    Then I should see "Tag one"
    And I should see "Tag two"

  @test-drupal @api @skipped
  # TODO: This doesn't work on Drupal 8/9/10 yet. For nodes the 'author' field
  # is called 'uid' and only accepts numerical IDs.
  Scenario: Create nodes with specific authorship
    Given users:
      | name     | mail            | status |
      | Joe User | joe@example.com | 1      |
    And "article" content:
      | title          | author   | body             | promote |
      | Article by Joe | Joe User | PLACEHOLDER BODY | 1       |
    When I am logged in as a user with the "administrator" role
    And I am on the homepage
    And I follow "Article by Joe"
    Then I should see the link "Joe User"

  @test-drupal @api
  Scenario: Create an article with multiple term references
    Given "tags" terms:
      | name      |
      | Tag one   |
      | Tag two   |
      | Tag,three |
      | Tag four  |
    And "article" content:
      | title           | body             | promote | field_tags                    |
    # Field values containing commas should be escaped with double quotes.
      | Article by Joe  | PLACEHOLDER BODY | 1       | Tag one, Tag two, "Tag,three" |
      | Article by Mike | PLACEHOLDER BODY | 1       | Tag four                      |
    When I am on the homepage
    Then I should see the link "Tag one"
    And I should see the link "Tag two"
    And I should see the link "Tag,three"
    And I should see the link "Tag four"

  @test-drupal @api
  Scenario: Readable created dates
    Given "article" content:
      | title        | body             | created            | status | promote |
      | Test article | PLACEHOLDER BODY | 07/27/2014 12:03am | 1      | 1       |
    When I am on the homepage
    Then I should see the text "27 July, 2014"

  @test-drupal @api
  Scenario: Node hooks are functioning
    Given "article" content:
      | title        | body        | published on       | status | promote |
      | Test article | PLACEHOLDER | 04/27/2013 11:11am | 1      | 1       |
    When I am on the homepage
    Then I should see the text "27 April, 2013"

  @test-drupal @api
  Scenario: Node edit access by administrator
    Given I am logged in as a user with the "administrator" role
    Then I should be able to edit an "article"

  @test-drupal @api
  Scenario: User hooks are functioning
    Given users:
      | First name | Last name | E-mail               |
      | Joe        | User      | joe.user@example.com |
    And I am logged in as a user with the "administrator" role
    When I visit "admin/people"
    Then I should see the link "Joe User"

  @test-drupal @api
  Scenario: Term hooks are functioning
    Given "tags" terms:
      | Label   |
      | Tag one |
      | Tag two |
    And I am logged in as a user with the "administrator" role
    When I go to "admin/structure/taxonomy/manage/tags/overview"
    Then I should see "Tag one"
    And I should see "Tag two"

  @test-drupal @api
  Scenario: Log in as a user with specific permissions
    Given I am logged in as a user with the "Administer content types" permission
    When I go to "admin/structure/types"
    Then I should see the link "Add content type"
