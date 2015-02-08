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

  @runthis
  Scenario: Test entity reference field handler
    Given "page" content:
      | title      |
      | Page one   |
      | Page two   |
      | Page three |
    When I am viewing a "post" content:
      | title                | Post title         |
      | body                 | PLACEHOLDER BODY   |
      | field_post_reference | Page one, Page two |
    Then I should see "Page one"
    And I should see "Page two"
