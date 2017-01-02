@api @d8
Feature: EntityContext
  In order to prove the Entity context is working properly
  As a developer
  I need to use the step definitions of this context

  # These scenarios assume a "standard" install of Drupal 7 and 8.

  Background:
    Given I am logged in as an administrator

  Scenario: Test multiple bundleless entities in "Given :entity_type entities"
    Given "user" entities:
      | name       | mail                      |
      | johndoe    | johndoe@example.com    |
      | fredbloggs | fredbloggs@example.com |
    When I am at "admin/people"
    Then I should see "johndoe"
    When I click "Edit" in the "johndoe" row
    Then the "mail" field should contain "johndoe@example.com"
    When I am at "admin/people"
    Then I should see "fredbloggs"
    When I click "Edit" in the "fredbloggs" row
    Then the "mail" field should contain "fredbloggs@example.com"

  Scenario: Test entities have been cleaned up after previous scenario
    When I am at "admin/people"
    Then I should not see "johndoe"
	And I should not see "fredbloggs"

  Scenario: Test single bundleless entity in "Given a :entity_type entity"
    Given a "user" entity:
      | name    | mail                   |
      | johndoe | johndoe@example.com |
    When I am at "admin/people"
    Then I should see "johndoe"
    When I click "Edit" in the "johndoe" row
    Then the "mail" field should contain "johndoe@example.com"
	
  Scenario: Test single bundled entity in "Given a :bundle :entity_type entity"
    Given a "page" "node" entity:
      | title     | body             |
      | Page one  | Some body text |
    When I am at "admin/content?type=page"
    Then I should see "Page one"
    When I click "Page one"
    Then I should see "Some body text" in the ".field--name-body" element

  Scenario: Test multiple bundled entities in "Given :bundle :entity_type entities"
    Given "page" "node" entities:
      | title     | body                 |
      | Page one  | Some body text       |
      | Page two  | Some more body text  |
    When I am at "admin/content?type=page"
    Then I should see "Page one"
    When I click "Page one"
    Then I should see "Some body text" in the ".field--name-body" element
    When I am at "admin/content?type=page"
    Then I should see "Page two"
    When I click "Page two"
    Then I should see "Some more body text" in the ".field--name-body" element

  Scenario: Test passing bundle as a column in "Given a :entity_type entity"
    Given a "node" entity:
      | type    | title       |
      | page    | Page one    |
	  | article | Article one | 
    When I am at "admin/content?type=page"
    Then I should see "Page one"
    When I am at "admin/content?type=article"
    Then I should see "Article one"

  Scenario: Test passing bundle as a column called "step_bundle" in "Given a :entity_type entity"
    Given a "node" entity:
      | step_bundle    | title       |
      | page           | Page one    |
	  | article        | Article one | 
    When I am at "admin/content?type=page"
    Then I should see "Page one"
    When I am at "admin/content?type=article"
    Then I should see "Article one"
	
  Scenario: Test comment entities, as they have different bundle_key and use entity_type as a field."
    Given "user" entities:
      | name      |
      | johndoe | 
	  | fredbloggs | 
	Given "user_comments" "comment" entities:
	  | subject   |  entity_id | entity_type |
	  | Great post      |  johndoe    | user        |
	  | Just one thing  |  fredbloggs | user        |
    When I am at "admin/content/comment"
    Then I should see "Great post"
    And I should see "Just one thing"

  Scenario: Test comment entities with bundle as a column
    Given "user" entities:
      | name       |
      | johndoe    | 
	  | fredbloggs | 
	Given "comment" entities:
	  | comment_type  | subject         |  entity_id  | entity_type |
	  | user_comments | Great post      |  johndoe    | user        |
	  | user_comments | Just one thing  |  fredbloggs | user        |
    When I am at "admin/content/comment"
    Then I should see "Great post"
    And I should see "Just one thing"	
	
  Scenario: Test bundleless entity in "Given I am viewing a :entity_type entity with the :label_name :label"
    Given I am viewing a "user" entity with the "name" "johndoe"
    Then I should see "johndoe" in the ".page-title" element

  Scenario: Test bundled entity in "Given I am viewing a :bundle :entity_type entity with the :label_name :label"
    Given I am viewing an "article" "node" entity with the "title" "Article one"
    Then I should see "Article one" in the ".page-title" element

  Scenario: Test bundleless entity in "Given I am viewing a :entity_type entity:"
    Given I am viewing a "user" entity:
      | name | johndoe        |
      | mail | johndoe@example.com |
    Then I should see "johndoe" in the ".page-title" element
    When I am at "admin/people"
    Then I should see "johndoe"
    When I click "Edit" in the "johndoe" row
    Then the "mail" field should contain "johndoe@example.com"

  Scenario: Test bundled entity in "Given I am viewing a :bundle :entity_type entity:"
    Given I am viewing an "article" "node" entity:
      | title | Article one    |
      | body  | Some body text |
    Then I should see "Article one" in the ".page-title" element
    And I should see "Some body text" in the ".field--name-body" element

  Scenario: Test passing bundle as a column in "Given I am viewing a :entity_type entity:"
    Given I am viewing a "node" entity:
      | type  | page           |
      | title | Page one       |
      | body  | Some body text |
    Then I should see "Page one" in the ".page-title" element
    And I should see "Some body text" in the ".field--name-body" element

  Scenario: Entity hooks are functioning
    Given a "user" entity:
      | First name  | Last name |
      | John        | Doe      |
    And I am logged in as a user with the "administrator" role
    When I visit "admin/people"
    Then I should see the link "John Doe"
