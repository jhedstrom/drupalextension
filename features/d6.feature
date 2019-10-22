@api @d6
Feature: Environment check

  Scenario: Frontpage
    Given I am not logged in
    And I am on the homepage
    Then I should see "User login"

  Scenario: assertAnonymousUser
    Given I am an anonymous user

  Scenario: assertAuthenticatedByRole
    Given I am logged in as a user with the "authenticated" role

  Scenario: assertAuthenticatedByRoleWithGivenFields
    Given I am logged in as a user with the "authenticated" role and I have the following fields:
    | name | test |

  Scenario: createNode
    Given I am viewing a story with the title "test"
    Then I should see "test"

  Scenario: createNodes
    Given article content:
      | title    | author     | status | created           |
      | My title | Joe Editor | 1      | 2014-10-17 8:00am |
    When I am viewing a content with the title "My title"
    Then I should see "My title"

  Scenario: createTerm
    Given I am viewing a tags term with the name "example tag"
    Then I should see "example tag"

  Scenario: createUsers
    Given I am logged in as a user with the "administer users" permission
    And users:
    | name     | mail         |
    | user foo | foo@bar.com  |
    | user bar | baz@bar.com  |
    When I visit "admin/user/user"
    Then I should see "user foo"
    And I should see "user bar"

  Scenario: create node with terms.
    Given tags terms:
      | name |
      | test-tag |
    And article content:
      | title    | status | taxonomy |
      | My title | 1      | test-tag |
    When I am on the homepage
    Then I should see "test-tag"
