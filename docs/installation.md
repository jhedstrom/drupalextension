# Installation

## Requirements

- PHP 8.1 or higher with the `curl`, `mbstring`, and `dom` extensions.
- [Composer](https://getcomposer.org/).
- Drupal 10 or 11.
- A web server reachable from the machine running Behat (for tests that
  hit a real browser, also a Mink-compatible driver such as
  [Symfony BrowserKit](https://symfony.com/doc/current/components/browser_kit.html)
  or a remote Selenium/WebDriver service).

Check the running PHP version:

```shell
php --version
```

## Install with Composer

From the root of your Drupal project:

```shell
composer require --dev drupal/drupal-extension
```

This installs the Drupal Extension along with Behat, Mink, and the Mink
Extension. The `vendor/bin/behat` binary becomes available immediately.

## Create `behat.yml`

The Drupal Extension is configured through a `behat.yml` file at the
root of your project. Start with a minimal configuration:

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

Replace `base_url` with the URL of the site you want to test (no
trailing slash).

For all configuration options including profiles and environment
variables, see [Configuration](configuration.md).

## Initialise Behat

```shell
vendor/bin/behat --init
```

This creates `tests/features/` and a `tests/bootstrap/FeatureContext.php`
stub:

```php
<?php

use Behat\Behat\Context\Context;
use Drupal\DrupalExtension\Context\RawDrupalContext;

class FeatureContext extends RawDrupalContext implements Context {

  public function __construct() {
  }

}
```

`FeatureContext` extends `RawDrupalContext`, which provides Drupal and
Mink session helpers without adding any step definitions. Add your own
project-specific steps here.

## Verify the install

List every step definition Behat can see:

```shell
vendor/bin/behat -di
```

You should see Drupal Extension steps such as:

```gherkin
default | Given I am an anonymous user
default | Given I am not logged in
default | Given I am logged in as a user with the :role role(s)
default | Given I am logged in as :name
```

If the list is empty or you get a `ContextNotFoundException`, double
check that `FeatureContext.php` lives next to `behat.yml` (or that
`autoload` in `behat.yml` points at the right directory).

## Next steps

- Pick a driver: [Drivers overview](drivers/README.md).
- Configure profiles for multiple environments: [Configuration](configuration.md).
- Browse the available step definitions: [`STEPS.md`](../STEPS.md).
