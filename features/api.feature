@api
Feature: DrupalContext
  In order to prove the Drupal context is working properly
  As a developer
  I need to use the step definitions of this context

  # These scenarios assume a "standard" install of Drupal 7.

  Scenario: Drush alias
    Given I am logged in as a user with the "authenticated user" role
    When I click "My account"
    Then I should see the heading "History"

  Scenario: Target links within table rows
    Given I am logged in as a user with the "administrator" role
    When I am at "admin/structure/types"
    And I click "manage fields" in the "Article" row
    Then I should be on "admin/structure/types/manage/article/fields"
    And I should see text matching "Add new field"

  Scenario: Find a heading in a region
    Given I am not logged in
    When I am on the homepage
    Then I should see the heading "User login" in the "left sidebar" region

  Scenario: Clear cache
    Given the cache has been cleared
    When I am on the homepage
    Then I should get a "200" HTTP response

  Scenario: Create a node
    Given I am logged in as a user with the "administrator" role
    When I am viewing an "article" node with the title "My article"
    Then I should see the heading "My article"

  Scenario: Run cron
    Given I am logged in as a user with the "administrator" role
    When I run cron
    And am on "admin/reports/dblog"
    Then I should see the link "Cron run completed"

  Scenario: Create many nodes
    Given "page" nodes:
    | title    |
    | Page one |
    | Page two |
    And "article" nodes:
    | title          |
    | First article  |
    | Second article |
    And I am logged in as a user with the "administrator" role
    When I go to "admin/content"
    Then I should see "Page one"
    And I should see "Page two"
    And I should see "First article"
    And I should see "Second article"

  Scenario: Create nodes with fields
    Given "article" nodes:
    | title                     | promote | body             |
    | First article with fields |       1 | PLACEHOLDER BODY |
    When I am on the homepage
    And follow "First article with fields"
    Then I should see the text "PLACEHOLDER BODY"

  Scenario: Create users
    Given users:
    | name     | mail            | status |
    | Joe User | joe@example.com | 1      |
    And I am logged in as a user with the "administrator" role
    When I visit "admin/people"
    Then I should see the link "Joe User"

  Scenario: Create a term
    Given I am logged in as a user with the "administrator" role
    When I am viewing a "tags" term with the name "My tag"
    Then I should see the heading "My tag"

  Scenario: Create many terms
    Given "tags" terms:
    | name    |
    | Tag one |
    | Tag two |
    And I am logged in as a user with the "administrator" role
    When I go to "admin/structure/taxonomy/tags"
    Then I should see "Tag one"
    And I should see "Tag two"

  Scenario: Create nodes with specific authorship
    Given users:
    | name     | mail            | status |
    | Joe User | joe@example.com | 1      |
    And "article" nodes:
    | title          | author   | body             | promote |
    | Article by Joe | Joe User | PLACEHOLDER BODY | 1       |
    When I am logged in as a user with the "administrator" role
    And I am on the homepage
    And I follow "Article by Joe"
    Then I should see the link "Joe User"

  Scenario: Create an article with multiple term references
    Given "tags" terms:
    | name      |
    | Tag one   |
    | Tag two   |
    | Tag three |
    | Tag four  |
    And "article" nodes:
    | title          | body             | promote | field_tags                            |
    | Article by Joe | PLACEHOLDER BODY | 1       | Tag one, Tag two, Tag three, Tag four |
    When I am logged in as a user with the "administrator" role
    And I am on the homepage
    And I follow "Article by Joe"
    Then I should see the link "Tag one"
    And I should see the link "Tag two"
