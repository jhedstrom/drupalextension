@api
Feature: FieldHandlers
  In order to prove field handling is working properly
  As a developer
  I need to use the step definitions of this context

  # @d7 scenarios assume a "standard" install of Drupal 7 and require
  # the feature "fixtures/drupal7/modules/behat_test" to enabled on the site.
  @d7 @runthis
  Scenario: Test various node field handlers in Drupal 7
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
    And I should see "Sunday, February 8, 2015"
    And I should see "to Tuesday, March 10, 2015"
    And I should see the link "Link 1"
    And I should see the link "Link 2"
    And I should see "One"
    And I should see "Two"

  @d7
  Scenario: Test various user field handlers in Drupal 7
    Given "tags" terms:
      | name      |
      | Tag one   |
      | Tag two   |
    And "page" content:
      | title      |
      | Page one   |
      | Page two   |
      | Page three |
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
    And I should see "Page one"
    And I should see "Page two"
    But I should not see "Page three"

  @d7
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

  # @d8 scenarios assume a "standard" install of Drupal 8 and require
  # the module "fixtures/drupal8/modules/behat_test" to enabled on the site.
  @d8
  Scenario: Test various node field handlers in Drupal 8
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
      | field_post_links     | Link 1 - http://example.com, Link 2 - http://example.com |
      | field_post_select    | One, Two                                                 |
    Then I should see "Page one"
    And I should see "Page two"
    And I should see "02/08/2015"
    And I should see the link "Link 1"
    And I should see the link "Link 2"
    And I should see "One"
    And I should see "Two"

  @d8
  Scenario: Test various user field handlers in Drupal 8
    Given "tags" terms:
      | name      |
      | Tag one   |
      | Tag two   |
    And "page" content:
      | title      |
      | Page one   |
      | Page two   |
      | Page three |
    And users:
      | name     | mail         | field_user_tags       | field_user_reference |
      | John Doe | john@doe.com | Tag one, Tag two      | Page one, Page two   |
    And I am logged in as a user with the "administrator" role
    When I visit "admin/people"
    Then I should see the link "John Doe"
    And I click "John Doe"
    Then I should see "Tag one"
    And I should see "Tag two"
    But I should not see "Tag three"
    And I should see "Page one"
    And I should see "Page two"
    But I should not see "Page three"



