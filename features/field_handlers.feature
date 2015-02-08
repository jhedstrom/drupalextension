@d7 @api
Feature: FieldHandlers
  In order to prove field handling is working properly
  As a developer
  I need to use the step definitions of this context

  # These scenarios assume a "standard" install of Drupal 7 and
  # require the feature "./features/modules/behat_test" to enabled on the site.

  Scenario: Test taxonomy term reference field handler
    Given "tags" terms:
      | name      |
      | Tag one   |
      | Tag two   |
      | Tag three |
      | Tag four  |
    And "article" content:
      | title           | body             | promote | field_tags                  |
      | Article by Joe  | PLACEHOLDER BODY |       1 | Tag one, Tag two, Tag three |
      | Article by Mike | PLACEHOLDER BODY |       1 | Tag four                    |
    When I am on the homepage
    Then I should see the link "Article by Joe"
    And I should see the link "Tag one"
    And I should see the link "Tag two"
    And I should see the link "Tag three"


  Scenario: Test node field handlers
    Given "page" content:
      | title      |
      | Page one   |
      | Page two   |
      | Page three |
    When I am viewing a "post" content:
      | title                | Post title                                               |
      | body                 | PLACEHOLDER BODY                                         |
      | field_post_reference | Page one, Page two                                       |
      | field_post_date      | 2015-02-08 17:45:00                                      |
      | field_post_dates     | 2015-02-10 17:45:00 - 2015-03-10 17:45:00                |
      | field_post_links     | Link 1 - http://example.com, Link 2 - http://example.com |
      | field_post_select    | One, Two                                                 |
    Then I should see "Page one"
    And I should see "Page two"
    And I should see "Sunday, February 8, 2015 - 18:45"
    And I should see "Tuesday, February 10, 2015 - 18:45 to Tuesday, March 10, 2015 - 18:45"
    And I should see the link "Link 1"
    And I should see the link "Link 2"
    And I should see "One"
    And I should see "Two"

  @runthis
  Scenario: Test user field handlers
    Given "tags" terms:
      | name      |
      | Tag one   |
      | Tag two   |
    And "page" content:
      | title      |
      | Page one   |
      | Page two   |
    And users:
      | name     | mail         | field_tags       | field_post_reference |
      | John Doe | john@doe.com | Tag one, Tag two | Page one, Page two   |
    And I am logged in as a user with the "administrator" role
    When I visit "admin/people"
    Then I should see the link "John Doe"
    And I click "John Doe"
    Then I should see the link "Tag one"
    And I should see the link "Tag two"
    But I should not see the link "Tag three"
