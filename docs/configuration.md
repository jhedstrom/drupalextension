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
  regions:
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
| `regions` | Maps human-readable region names to CSS selectors. |
| `selectors` | CSS selectors for Drupal status, error, and success messages. |
| `suppress_deprecations` | Silences `[Deprecation]` notices. See [Suppressing deprecation notices](#suppressing-deprecation-notices). |
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
    logout_confirm_url: '/user/logout/confirm'
    log_out: 'Sign out'
    log_in: 'Sign in'
    password_field: 'Enter your password'
    username_field: 'Nickname'
```

## Regions

Define page regions so steps such as `I press "Search" in the "header"
region` work without writing custom PHP:

```yaml
Drupal\DrupalExtension:
  regions:
    header: '#header'
    content: '#main'
    footer: '#footer'
    'right sidebar': '#sidebar-second'
```

Region steps work against any HTML page - resolution goes through Mink's
custom `region` selector and has no dependency on the Drupal driver.

See [Blackbox driver - Region steps](drivers/blackbox.md#region-steps)
for the full pattern.

> **Deprecated:** the `region_map` key is the legacy name for the same
> map. It still works in 6.0 but emits a deprecation notice and is
> removed in 6.1. Rename it to `regions`. If both keys are present, an
> entry under `regions` overrides the same key under `region_map`.

## Suppressing deprecation notices

The extension writes `[Deprecation] ...` notices to `STDERR` when it
detects deprecated configuration or runtime patterns. Behat's error
handler escalates `E_USER_DEPRECATED` into step failures, so notices
are written directly to `STDERR` rather than through `trigger_error()`.
Each unique message is emitted at most once per process.

Two switches control whether the notices surface.

**1. The `suppress_deprecations` configuration key** silences notices
across both layers (extension load and context runtime). Set it on the
profile that needs to be quiet:

```yaml
default:
  extensions:
    Drupal\DrupalExtension:
      suppress_deprecations: true
```

**2. The `BEHAT_DRUPALEXTENSION_SUPPRESS_DEPRECATIONS` environment
variable** overrides the configuration in either direction. It accepts
the parseable boolean spellings `1`/`0`, `true`/`false`, `yes`/`no`,
and `on`/`off` (case-insensitive). An unset or unparseable value yields
no override:

```shell
# Force suppression for one CI run regardless of behat.yml.
BEHAT_DRUPALEXTENSION_SUPPRESS_DEPRECATIONS=1 vendor/bin/behat

# Force notices to surface even when suppress_deprecations: true is set.
BEHAT_DRUPALEXTENSION_SUPPRESS_DEPRECATIONS=0 vendor/bin/behat
```

Use `BEHAT_DRUPALEXTENSION_SUPPRESS_DEPRECATIONS` for ad-hoc overrides;
use `suppress_deprecations` in config when suppression should be
persistent for the project. If you prefer `BEHAT_PARAMS`, set the
`suppress_deprecations` config key there - that drives the same config
path. The dedicated env var remains a separate override channel and
takes precedence when both are set.

## Disabling automatic cleanup

After every scenario, `RawDrupalContext` deletes the entities, users,
and roles it created and logs the current user out. This keeps the
database tidy between scenarios but makes it hard to inspect state when
a scenario fails - by the time you look, the data is gone.

Set `BEHAT_DRUPALEXTENSION_DISABLE_CLEANUP=1` to skip the entity, user,
and role teardown for the run:

```shell
BEHAT_DRUPALEXTENSION_DISABLE_CLEANUP=1 vendor/bin/behat
```

Recognised "enabled" spellings (case-insensitive, whitespace trimmed):
`1`, `true`, `yes`, `on`. Any other value, or unsetting the variable,
leaves cleanup running.

The toggle only affects entity, user, and role teardown. Config
revert (`ConfigContext`), mail re-enable (`MailContext`), random
variable reset (`RandomContext`), and static cache clearing all still
run.

The post-scenario logout is also skipped, so the session of the last
logged-in user stays open. This is deliberate - the whole point of the
flag is to let you load the failing page in a browser and look around.
The flip side is that subsequent scenarios in the same run inherit
that session, which may change their behaviour.

This is intended for ad-hoc local debugging. Do not enable it on CI -
leftover data and sessions will leak into subsequent scenarios in the
same run and into subsequent runs against the same database.
