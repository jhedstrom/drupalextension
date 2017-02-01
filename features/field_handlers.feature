@api
Feature: FieldHandlers
  In order to prove field handling is working properly
  As a developer
  I need to use the step definitions of this context

  # @d7 and @d8 scenarios assume a "standard" install of Drupal and require the
  # feature "fixtures/drupalN/modules/behat_test" to enabled on the site.
  @d7 @d8
  Scenario: Test various node field handlers in Drupal 7 and 8
    Given "page" content:
      | title      |
      | Page one   |
      | Page two   |
      | Page three |
    When I am viewing a "post" content:
      | title                | Post title                                                                       |
      | body                 | PLACEHOLDER BODY                                                                 |
      | field_post_reference | Page one, Page two                                                               |
      | field_post_date      | 2015-02-08 17:45:00                                                              |
      | field_post_links     | Link 1 - http://example.com, Link 2 - http://example.com                         |
      | field_post_select    | One, Two                                                                         |
      | field_post_address   | country: BE - locality: Brussel - thoroughfare: Louisalaan 1 - postal_code: 1000 |
    Then I should see "Post title"
    And I should see "PLACEHOLDER BODY"
    And I should see "Page one"
    And I should see "Page two"
    And I should see "Sunday, February 8, 2015"
    And I should see the link "Link 1"
    And I should see the link "Link 2"
    And I should see "One"
    And I should see "Two"
    And I should see "Belgium"
    And I should see "Brussel"
    And I should see "1000"
    And I should see "Louisalaan 1"

  # This is identical to the previous test, but uses human readable names for
  # the field names. This is better from a BDD standpoint. Please have a look at
  # FeatureContext::transformPostContentTable() to see how the mapping between
  # the machine names and human readable names is defined.
  @d7 @d8
  Scenario: Test using human readable names for fields using @Transform
    Given "page" content:
      | title      |
      | Page one   |
      | Page two   |
      | Page three |
    When I am viewing a "post" content:
      | title     | Post title                                                                       |
      | body      | PLACEHOLDER BODY                                                                 |
      | reference | Page one, Page two                                                               |
      | date      | 2015-02-08 17:45:00                                                              |
      | links     | Link 1 - http://example.com, Link 2 - http://example.com                         |
      | select    | One, Two                                                                         |
      | address   | country: BE - locality: Brussel - thoroughfare: Louisalaan 1 - postal_code: 1000 |
    Then I should see "Page one"
    And I should see "Page two"
    And I should see "Sunday, February 8, 2015"
    And I should see the link "Link 1"
    And I should see the link "Link 2"
    And I should see "One"
    And I should see "Two"
    And I should see "Belgium"
    And I should see "Brussel"
    And I should see "1000"
    And I should see "Louisalaan 1"

  @d7 @d8
  Scenario: Test alternative syntax for named field columns on node content
    When I am viewing a "post" content:
      | title                           | Post title                  |
      | field_post_address:country      | FR                          |
      | field_post_address:locality     | Paris                       |
      | field_post_address:thoroughfare | 1 Avenue des Champs Elysées |
      | field_post_address:postal_code  | 75008                       |
    Then I should see "France"
    And I should see "Paris"
    And I should see "1 Avenue des Champs Elysées"
    And I should see "75008"

  @d7 @d8
  Scenario: Test shorthand syntax for named field columns on node content
    When I am viewing a "post" content:
      | title                      | Post title      |
      | field_post_address:country | GB              |
      | :locality                  | London          |
      | :thoroughfare              | 1 Oxford Street |
      | :postal_code               | W1D 1AN         |
    Then I should see "United Kingdom"
    And I should see "London"
    And I should see "1 Oxford Street"
    And I should see "W1D 1AN"

  @d7 @d8
  Scenario: Test multivalue fields with named field columns on node content
    When I am viewing a "post" content:
      | title                      | Post title                             |
      | field_post_address:country | IT, JP                                 |
      | :locality                  | Milan, Tokyo                           |
      | :thoroughfare              | 1 Corso Buenos Aires, Shibuya Crossing |
      | :postal_code               | 20124, 150-0040                        |
    Then I should see "Italy"
    And I should see "Milan"
    And I should see "1 Corso Buenos Aires"
    And I should see "20124"
    And I should see "Japan"
    And I should see "Tokyo"
    And I should see "Shibuya Crossing"
    And I should see "150-0040"

  @d7 @d8
  Scenario: Test various user field handlers in Drupal 7.
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
      | name     | mail         | field_tags       | field_post_reference | field_post_address                                                               |
      | Jane Doe |              |                  |                      |                                                                                  |
      | John Doe | john@doe.com | Tag one, Tag two | Page one, Page two   | country: BE - locality: Brussel - thoroughfare: Louisalaan 1 - postal_code: 1000 |
    And I am logged in as a user with the "administrator" role
    When I visit "admin/people"
    Then I should see the link "Jane Doe"
    And I should see the link "John Doe"
    When I click "John Doe"
    Then I should see the link "Tag one"
    And I should see the link "Tag two"
    But I should not see the link "Tag three"
    And I should see "Page one"
    And I should see "Page two"
    But I should not see "Page three"
    And I should see "Belgium"
    And I should see "Brussel"
    And I should see "1000"
    And I should see "Louisalaan 1"

  @d7 @d8
  Scenario: Test using @Transform to provide human friendly aliases for named field columns
    Given users:
      | name     | mail             | street        | city     | postcode | country |
      | Jane Doe | jane@example.com | Pioneer Place | Portland | OR 97204 | US      |
    And I am logged in as a user with the "administrator" role
    When I visit "admin/people"
    And I click "Jane Doe"
    Then I should see "United States"
    And I should see "Portland"
    And I should see "Pioneer Place"
    And I should see "OR 97204"

  @d7 @d8
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
      | Article by Jane |                  |         |                             |
    And I am logged in as a user with the "administrator" role
    When I visit "admin/content"
    Then I should see the link "Article by Joe"
    And I should see the link "Article by Mike"
    And I should see the link "Article by Jane"
    When I am on the homepage
    Then I should see the link "Article by Joe"
    And I should see the link "Tag one"
    And I should see the link "Tag two"
    And I should see the link "Tag three"
    And I should see the link "Article by Mike"
    And I should see the link "Tag four"
    And I should see the link "Article by Joe"
    And I should not see the link "Article by Jane"

  @d7
  # There is no support for date ranges in D8 yet, so only test D7 for now.
  Scenario: Test date ranges in Drupal 7
    When I am viewing a "post" content:
      | title                | Post title                                                                       |
      | body                 | PLACEHOLDER BODY                                                                 |
      | field_post_dates     | 2015-02-10 17:45:00 - 2015-03-10 17:45:00                                        |
    Then I should see "to Tuesday, March 10, 2015"
