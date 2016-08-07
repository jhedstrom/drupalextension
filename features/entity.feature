@api @d8
Feature: EntityContext
  In order to prove the Entity context is working properly
  As a developer
  I need to use the step definitions of this context

  # These scenarios assume a "standard" install of Drupal 7 and 8.

  Background:
    Given I am logged in as an administrator

  Scenario: Test single bundleless entity in "Given a :entity_type entity"
    Given a "user" entity:
      | name             | mail                     |
      | entity_testuser1 | testuser1@testdomain.com |
    When I am at "admin/people"
    Then I should see "entity_testuser1"
    When I click "entity_testuser1"
    And I click "Edit"
    Then the "mail" field should contain "testuser1@testdomain.com"

  Scenario: Test entities have been cleaned up after previous scenario
    When I am at "admin/people"
    Then I should not see "entity_testuser1"

  Scenario: Test multiple bundleless entities in "Given :entity_type entities"
    Given "user" entities:
      | name             | mail                     |
      | entity_testuser1 | testuser1@testdomain.com |
      | entity_testuser2 | testuser2@testdomain.com |
    When I am at "admin/people"
    Then I should see "entity_testuser1"
    When I click "entity_testuser1"
    And I click "Edit"
    Then the "mail" field should contain "testuser1@testdomain.com"
    When I am at "admin/people"
    Then I should see "entity_testuser2"
    When I click "entity_testuser2"
    And I click "Edit"
    Then the "mail" field should contain "testuser2@testdomain.com"

  Scenario: Test single bundled entity in "Given a :bundle :entity_type entity"
    Given a "page" "node" entity:
      | title             | body             |
      | entity_testnode1  | Page body text 1 |
    When I am at "admin/content?status=All&type=page&title=&langcode=All"
    Then I should see "entity_testnode1"
    When I click "entity_testnode1"
    Then I should see "Page body text" in the ".field--name-body" element

  Scenario: Test multiple bundled entities in "Given :bundle :entity_type entities"
    Given "page" "node" entities:
      | title             | body             |
      | entity_testnode1  | Page body text 1 |
      | entity_testnode2  | Page body text 2 |
    When I am at "admin/content?status=All&type=page&title=&langcode=All"
    Then I should see "entity_testnode1"
    When I click "entity_testnode1"
    Then I should see "Page body text 1" in the ".field--name-body" element
    When I am at "admin/content?status=All&type=page&title=&langcode=All"
    Then I should see "entity_testnode2"
    When I click "entity_testnode2"
    Then I should see "Page body text 2" in the ".field--name-body" element

  Scenario: Test passing bundle as a field in "Given a :entity_type entity"
    Given a "node" entity:
      | type | title             | body             |
      | page | entity_testnode1  | Page body text 1 |
    When I am at "admin/content?status=All&type=page&title=&langcode=All"
    Then I should see "entity_testnode1"
    When I click "entity_testnode1"
    Then I should see "Page body text" in the ".field--name-body" element

  Scenario: Test bundleless entity in "Given I am viewing a :entity_type entity with the :label_name :label"
    Given I am viewing a "user" entity with the "name" "entity_testuser1"
    Then I should see "entity_testuser1" in the ".page-title" element

  Scenario: Test bundled entity in "Given I am viewing a :bundle :entity_type entity with the :label_name :label"
    Given I am viewing an "article" "node" entity with the "title" "entity_testnode1"
    Then I should see "entity_testnode1" in the ".page-title" element

  Scenario: Test bundleless entity in "Given I am viewing a :entity_type entity:"
    Given I am viewing a "user" entity:
      | name | entity_testuser1        |
      | mail | testuser1@testdomain.com |
    Then I should see "entity_testuser1" in the ".page-title" element
    When I click "Edit"
    Then the "mail" field should contain "testuser1@testdomain.com"

  Scenario: Test bundled entity in "Given I am viewing a :bundle :entity_type entity:"
    Given I am viewing an "article" "node" entity:
      | title | Article title     |
      | body  | Article body text |
    Then I should see "Article title" in the ".page-title" element
    And I should see "Article body text" in the ".field--name-body" element

  Scenario: Test passing bundle as a field in "Given I am viewing a :entity_type entity:"
    Given I am viewing a "node" entity:
      | type  | page           |
      | title | Page title     |
      | body  | Page body text |
    Then I should see "Page title" in the ".page-title" element
    And I should see "Page body text" in the ".field--name-body" element

  Scenario: Entity hooks are functioning
    Given a "user" entity:
      | First name | Last name |
      | Joe        | User      |
    And I am logged in as a user with the "administrator" role
    When I visit "admin/people"
    Then I should see the link "Joe User"
