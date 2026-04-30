# Drush driver

The Drush driver lets Behat create users, log in, reset passwords, and
manage content via Drush commands. The main advantage over the
[Drupal API driver](drupal-api.md) is that the site can live on a
different host than Behat, as long as Drush can reach it.

## Requirements

- Drush 12 or higher installed in the project under test.
- Drush must be able to bootstrap the target site, either through:
  - A `--root` path (local sites), or
  - A [Drush site alias](https://www.drush.org/latest/site-aliases/)
    (local or remote sites).

## Enable the driver

In `behat.yml`, set `api_driver: 'drush'` and configure the `drush`
block:

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
      api_driver: 'drush'
      drush:
        alias: '@self'
```

`api_driver: 'drush'` only applies to scenarios tagged `@api`. The
Blackbox driver still handles untagged scenarios.

## Pointing Drush at your site

### Local site - root path

Set `root` to the absolute path of the Drupal codebase:

```yaml
Drupal\DrupalExtension:
  blackbox: ~
  api_driver: 'drush'
  drush:
    root: '/var/www/drupal'
```

### Local or remote site - Drush alias

Use a [Drush alias](https://www.drush.org/latest/site-aliases/) for
multi-environment setups or remote hosts. Aliases live in
`drush/sites/<group>.site.yml` files inside your project:

```yaml
# drush/sites/example.site.yml
local:
  root: /var/www/drupal/web
  uri: 'http://localhost'

stage:
  host: stage.example.com
  user: deploy
  root: /var/www/drupal/web
  uri: 'https://stage.example.com'
```

Then point the Drupal Extension at the alias:

```yaml
Drupal\DrupalExtension:
  api_driver: 'drush'
  drush:
    alias: '@example.local'
```

For remote hosts, Behat invokes Drush over SSH; you need passwordless
SSH key access to the target machine.

## Tagging scenarios

```gherkin
Feature: Drush driver
  In order to demonstrate driver selection
  As a developer
  I need to tag scenarios that need privileged access

  Scenario: Untagged scenario uses Blackbox driver and fails to create users
    Given I am logged in as a user with the "authenticated user" role
    When I click "My account"
    Then I should see the heading "History"

  @api
  Scenario: Tagged scenario uses the Drush driver and succeeds
    Given I am logged in as a user with the "authenticated user" role
    When I click "My account"
    Then I should see the heading "History"
```

The first scenario fails with:

```
No ability to create users in Drupal\Driver\BlackboxDriver.
Put `@api` into your feature and add an api driver
(ex: `api_driver: drupal`) in behat.yml.
```

## What the Drush driver adds

In addition to all Blackbox steps, the Drush driver gives access to
user creation, login, term creation, and direct Drush command
invocation:

```gherkin
@api
Feature: Drush driver examples

  Scenario: Log in as a created user
    Given I am logged in as a user with the "authenticated user" role
    When I click "My account"
    Then I should see the heading "History"

  Scenario: Manage fields via the admin UI
    Given I am logged in as a user with the "administrator" role
    When I am at "admin/structure/types"
    And I click "manage fields" in the "Article" row
    Then I should be on "admin/structure/types/manage/article/fields"

  Scenario: Clear cache and verify the homepage
    Given the cache has been cleared
    When I am on the homepage
    Then I should get a "200" HTTP response
```

For the complete list of step definitions enabled by the Drush driver,
see [`STEPS.md`](../../STEPS.md).

## Calling Drush directly

The `DrushContext` exposes the `Given I run drush ":command"` step,
which forwards arbitrary commands through the configured alias. See
[Contexts](../contexts.md#drushcontext) for details.
