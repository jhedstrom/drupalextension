# Drupal API driver

The Drupal API driver is the fastest and most powerful of the three
drivers. It bootstraps Drupal in the same PHP process as Behat and
talks to the entity API directly - no HTTP, no Drush.

The trade-off: Behat must run on the same host as the Drupal site,
with read access to the codebase and database.

## Enable the driver

In `behat.yml`, set `api_driver: 'drupal'` and point `drupal_root` at
the directory containing `index.php`:

```yaml
default:
  suites:
    default:
      contexts:
        - FeatureContext
        - Drupal\DrupalExtension\Context\DrupalContext
        - Drupal\DrupalExtension\Context\MinkContext
  extensions:
    Drupal\MinkExtension:
      browserkit_http: ~
      base_url: http://example.org/
    Drupal\DrupalExtension:
      blackbox: ~
      api_driver: 'drupal'
      drupal:
        drupal_root: '/var/www/drupal/web'
```

> Leave `drush:` configuration in place if you also use it - the
> `api_driver` value is what selects which driver runs `@api`
> scenarios.

## What the Drupal API driver adds

All steps available to the Drush driver work here, plus you can create
content with arbitrary fields, manage taxonomy terms, run cron, and
more.

```gherkin
@api
Feature: Drupal API driver examples

  Scenario: Create a node
    Given I am logged in as a user with the "administrator" role
    When I am viewing an "article" content with the title "My article"
    Then I should see the heading "My article"

  Scenario: Run cron
    Given I am logged in as a user with the "administrator" role
    When I run cron
    And am on "admin/reports/dblog"
    Then I should see the link "Cron run completed"

  Scenario: Create many nodes
    Given the following "page" content:
      | title    |
      | Page one |
      | Page two |
    And the following "article" content:
      | title          |
      | First article  |
      | Second article |
    And I am logged in as a user with the "administrator" role
    When I go to "admin/content"
    Then I should see "Page one"
    And I should see "First article"

  Scenario: Create nodes with arbitrary fields
    Given the following "article" content:
      | title                     | promote | body             |
      | First article with fields |       1 | PLACEHOLDER BODY |
    When I am on the homepage
    And follow "First article with fields"
    Then I should see the text "PLACEHOLDER BODY"

  Scenario: Create users
    Given the following users:
      | name     | mail            | status |
      | Joe User | joe@example.com | 1      |
    And I am logged in as a user with the "administrator" role
    When I visit "admin/people"
    Then I should see the link "Joe User"

  Scenario: Log in as a user created during the scenario
    Given the following users:
      | name      | status |
      | Test user |      1 |
    When I am logged in as "Test user"
    Then I should see the link "Log out"

  Scenario: Create taxonomy terms
    Given the following "tags" terms:
      | name    |
      | Tag one |
      | Tag two |
    And I am logged in as a user with the "administrator" role
    When I go to "admin/structure/taxonomy/manage/tags/overview"
    Then I should see "Tag one"

  Scenario: Reference taxonomy terms from a node
    Given the following "tags" terms:
      | name      |
      | Tag one   |
      | Tag two   |
    And the following "article" content:
      | title             | field_tags         |
      | My first article  | Tag one            |
      | My second article | Tag one, Tag two   |
    When I am logged in as a user with the "administrator" role
    And I am on the homepage
```

For the complete list of step definitions, see
[`STEPS.md`](../../STEPS.md).

## Customising entity creation

Hook into the entity-creation lifecycle from your custom contexts to
tweak the data that ends up in the database. See [Hooks](../hooks.md)
for the supported hook annotations and an example.
