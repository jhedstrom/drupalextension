# Drivers

The Drupal Extension ships three drivers for interacting with a Drupal
10 or 11 site. Each driver makes different trade-offs between speed,
isolation, and what it can do to the site.

| Feature | Blackbox | Drush | Drupal API |
| --- | --- | --- | --- |
| Map regions | Yes | Yes | Yes |
| Create users | No | Yes | Yes |
| Create nodes | No | Yes | Yes |
| Create vocabularies and terms | No | Yes | Yes |
| Direct entity API access | No | No | Yes |
| Tests run on a different host than the site | Yes | Yes | No |
| Speed (relative) | Slowest | Faster | Fastest |

## Choosing a driver

- **Blackbox** is the right starting point. It assumes no privileged
  access and exercises the site purely through its UI - the same way a
  user would. Slowest because every step renders a page.
- **Drush** lets you create users, nodes, and taxonomy terms via Drush
  commands. The site can live on a different host as long as Drush can
  reach it (typically over SSH using a Drush alias).
- **Drupal API** is the fastest and most powerful option but requires
  Behat and the Drupal site to share a filesystem. It bootstraps Drupal
  in-process and uses the entity API directly.

## Tagging scenarios

Behat scenarios with no tag use the Blackbox driver. To opt into the
Drush or Drupal API driver, tag the scenario with `@api`:

```gherkin
@api
Scenario: Create a node
  Given I am logged in as a user with the "administrator" role
  When I am viewing an "article" content with the title "My article"
  Then I should see the heading "My article"
```

Which driver runs `@api` scenarios is decided by `api_driver` in
`behat.yml`:

```yaml
Drupal\DrupalExtension:
  blackbox: ~
  api_driver: 'drupal'
```

If you tag a scenario with `@api` but `api_driver` is set to
`blackbox` (or unset), creation steps fail with:

```
No ability to create users in Drupal\Driver\BlackboxDriver.
Put `@api` into your feature and add an api driver
(ex: `api_driver: drupal`) in behat.yml.
```

## Read on

- [Blackbox driver](blackbox.md) - regions, message selectors, and
  string overrides available without privileged access.
- [Drush driver](drush.md) - user and content creation via Drush.
- [Drupal API driver](drupal-api.md) - direct Drupal API access.
