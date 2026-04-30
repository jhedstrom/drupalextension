# Configuration

The Drupal Extension is configured through `behat.yml`. This page
covers the configuration options the extension exposes, how to use
profiles, and how to inject environment-specific values.

## File structure

A typical `behat.yml` declares one or more profiles. Each profile lists
the contexts, suites, and extensions to load. Behat loads the
`default` profile unless you pass `--profile=<name>`.

```yaml
default:
  autoload: ['%paths.base%/tests/bootstrap']
  suites:
    default:
      paths: ['%paths.base%/tests/features']
      contexts:
        - FeatureContext
        - Drupal\DrupalExtension\Context\DrupalContext
        - Drupal\DrupalExtension\Context\MinkContext
        - Drupal\DrupalExtension\Context\MessageContext
  extensions:
    Drupal\MinkExtension:
      browserkit_http: ~
      base_url: http://example.org/
    Drupal\DrupalExtension:
      blackbox: ~
```

## Mink Extension options

The `Drupal\MinkExtension` block configures the browser drivers Behat
uses to interact with the site:

- `base_url` - URL of the site under test, no trailing slash.
- `browserkit_http` - In-process HTTP client. Fast, no JavaScript.
- `goutte` / `selenium2` / `webdriver` - Available if you install the
  matching package; required for JavaScript-driven scenarios.

See the [Mink Extension documentation](https://github.com/Behat/MinkExtension)
for the full set of options.

## Drupal Extension options

```yaml
Drupal\DrupalExtension:
  blackbox: ~
  api_driver: 'drupal'
  drush:
    root: '/var/www/drupal'
    alias: '@self'
  drupal:
    drupal_root: '/var/www/drupal'
  region_map:
    header: '#header'
    content: '#main'
    footer: '#footer'
  selectors:
    message_selector: '.messages'
    error_message_selector: '.messages.messages-error'
    success_message_selector: '.messages.messages-status'
  text:
    log_in: 'Log in'
    log_out: 'Log out'
    password_field: 'Password'
    username_field: 'Username'
```

| Key | Purpose |
| --- | --- |
| `blackbox` | Enables the Blackbox driver. |
| `api_driver` | `'blackbox'`, `'drush'`, or `'drupal'`. Used by `@api` scenarios. |
| `drush` | Configuration for the [Drush driver](drivers/drush.md). |
| `drupal` | Configuration for the [Drupal API driver](drivers/drupal-api.md). |
| `region_map` | Maps human-readable region names to CSS selectors. |
| `selectors` | CSS selectors for Drupal status, error, and success messages. |
| `text` | Localised or themed strings used by the built-in steps. |

## Profiles

Profiles inherit from `default` and override specific settings. They
are useful for switching between local, CI, and remote environments.

```yaml
default:
  extensions:
    Drupal\MinkExtension:
      browserkit_http: ~
      base_url: http://example.org/
    Drupal\DrupalExtension:
      blackbox: ~

local:
  extensions:
    Drupal\MinkExtension:
      base_url: http://localhost:8080
    Drupal\DrupalExtension:
      api_driver: 'drupal'
      drupal:
        drupal_root: '../web'
```

Run a profile with:

```shell
vendor/bin/behat --profile=local
```

Note: contexts under `suites` are replaced wholesale by the inheriting
profile, not merged. Repeat all contexts you need under each profile.

## Environment variables (`BEHAT_PARAMS`)

Some `behat.yml` values change per environment - the `base_url` on a
laptop is different from CI, and the Drupal root path differs between
machines. Avoid committing these to `behat.yml` by passing them through
the `BEHAT_PARAMS` environment variable.

`BEHAT_PARAMS` is a JSON object that Behat merges over the values in
`behat.yml`:

```json
{
  "extensions": {
    "Drupal\\MinkExtension": {
      "base_url": "http://myproject.localhost"
    },
    "Drupal\\DrupalExtension": {
      "drupal": {
        "drupal_root": "/var/www/myproject"
      }
    }
  }
}
```

Export it as a single line:

```shell
export BEHAT_PARAMS='{"extensions":{"Drupal\\MinkExtension":{"base_url":"http://myproject.localhost"},"Drupal\\DrupalExtension":{"drupal":{"drupal_root":"/var/www/myproject"}}}}'
```

The values you put in `BEHAT_PARAMS` must be **removed or commented
out** in `behat.yml`. If both define a key, the `behat.yml` value wins.

```yaml
default:
  extensions:
    Drupal\MinkExtension:
      browserkit_http: ~
      # base_url comes from BEHAT_PARAMS
    Drupal\DrupalExtension:
      blackbox: ~
      # drupal.drupal_root comes from BEHAT_PARAMS
```

## Custom text strings

If your site overrides the default English labels for login, logout,
or message classes, point the extension at the right strings:

```yaml
Drupal\DrupalExtension:
  text:
    login_url: '/user'
    logout_url: '/user/logout'
    log_out: 'Sign out'
    log_in: 'Sign in'
    password_field: 'Enter your password'
    username_field: 'Nickname'
```

## Region map

Define site regions so steps such as `I press "Search" in the "header"
region` work without writing custom PHP:

```yaml
Drupal\DrupalExtension:
  region_map:
    header: '#header'
    content: '#main'
    footer: '#footer'
    'right sidebar': '#sidebar-second'
```

See [Blackbox driver - Region steps](drivers/blackbox.md#region-steps)
for the full pattern.
