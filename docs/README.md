<div align="center">
  <img height="100px" src="assets/beehat.png" alt="Behat Drupal Extension Logo">
</div>

<h1 align="center">Drupal Extension Documentation</h1>

The Drupal Extension is an integration layer between
[Behat](http://behat.org), [Mink Extension](https://github.com/Behat/MinkExtension),
and [Drupal](https://www.drupal.org/). It provides step definitions for
testing Drupal sites with Behavior-Driven Development.

## Contents

1. [Installation](installation.md) - Requirements, Composer install, first run.
2. [Configuration](configuration.md) - `behat.yml` options, profiles,
   `BEHAT_PARAMS` environment variables.
3. [Drivers](drivers/README.md) - Capability matrix and choosing a driver.
   - [Blackbox](drivers/blackbox.md) - No privileged access; UI only.
   - [Drush](drivers/drush.md) - User and content creation via Drush.
   - [Drupal API](drivers/drupal-api.md) - Direct Drupal API access.
4. [Contexts](contexts.md) - Available context classes and how to compose them.
5. [Hooks](hooks.md) - `@beforeNodeCreate`, `@beforeTermCreate`,
   `@beforeUserCreate`.
6. [Writing tests](writing-tests.md) - `@api` tagging, regions, custom contexts.

## Reference material

- [Step definitions](../STEPS.md) - Generated reference for every step
  shipped with the extension.
- [Upgrading guide](../UPGRADING.md) - Breaking changes and upgrade
  instructions for `5.x` to `6.x`.
- [Contributing](../CONTRIBUTING.md) - Development setup and
  contribution guidelines.

## Quick start

```shell
composer require --dev drupal/drupal-extension
```

Create a minimal `behat.yml`:

```yaml
default:
  autoload: ['%paths.base%/tests/bootstrap']
  suites:
    default:
      paths: ['%paths.base%/tests/features']
      contexts:
        - Drupal\DrupalExtension\Context\DrupalContext
  extensions:
    Drupal\MinkExtension:
      browserkit_http: ~
      base_url: http://example.org/
    Drupal\DrupalExtension:
      blackbox: ~
```

Initialise Behat and list available steps:

```shell
vendor/bin/behat --init
vendor/bin/behat -di
```

For full setup details, see [Installation](installation.md).
