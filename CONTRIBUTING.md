# Contributing

Features and bug fixes are welcome! First-time contributors can
jump in with the issues tagged [good first issue](https://github.com/jhedstrom/drupalextension/issues?q=is%3Aissue+is%3Aopen+label%3A%22good+first+issue%22).

## How this project works

The Behat Drupal Extension is a bridge between
[Behat](http://behat.org) (a BDD testing framework) and
[Drupal](https://www.drupal.org) (a content management system).
It provides pre-built step definitions like
`Given I am logged in as a user with the "administrator" role`
so that Drupal developers can write human-readable tests for
their sites without having to implement these steps themselves.

### Drivers

The extension supports three drivers, each providing a different
level of access to Drupal:

- **Blackbox** — The simplest driver. It only interacts with the
  site through the browser (HTTP requests), with no knowledge of
  Drupal internals. This is useful for testing any web page,
  including static HTML. Steps like "I should see the heading"
  or "I should see the link in the region" work with this driver.

- **Drupal API** — Bootstraps Drupal directly in PHP, allowing
  the tests to create and manipulate content, users, and
  taxonomy terms programmatically. Steps like
  `Given users:` or `Given I am logged in as a user with the
  "editor" role` use this driver to set up test data without
  going through the browser.

- **Drush** — Uses the [Drush](https://www.drush.org) command
  line tool to interact with Drupal. Steps like
  `Given I run drush "cache-rebuild"` use this driver.

### What are we testing?

This repository tests the extension itself — the step
definitions, context classes, and hooks that it provides. The
tests verify that each step definition works correctly against
a real Drupal site.

There are two types of tests:

- **Positive tests** verify that steps work when used correctly
  (e.g., "I am logged in" actually logs the user in).
- **Negative tests** verify that steps produce clear error
  messages when something goes wrong (e.g., trying to log in
  as a non-existent user).

The test suites are organized by driver:

- `test-bdd-blackbox` — Tests steps that only need HTTP access
  (the blackbox driver). These run against static HTML fixtures.
- `test-bdd-drupal` — Tests steps that need the Drupal API or
  Drush drivers. These run against a real Drupal installation.
- `test-bdd-drupal-https` — Tests HTTPS-specific functionality.

Unit tests (PHPSpec) cover the internal logic of context classes
and annotation parsing without needing a running Drupal site.

## Setting up the local environment

The local development environment uses Docker Compose to run
the extension's test suite against a real Drupal installation.
You can test with different combinations of PHP and Drupal
versions to match the CI matrix.

You'll need [Docker and Docker Compose](https://docs.docker.com/engine/install/)
and [Ahoy](https://github.com/ahoy-cli/ahoy) for running
commands.

The environment consists of several services:

- **php** — PHP-FPM backend that runs Drupal. All test commands
  execute inside this container.
- **database** — MariaDB database for the Drupal site.
- **drupal** — Nginx web server for the Drupal site, used by
  Behat tests that require a running Drupal installation
  (`@api` tagged tests).
- **blackbox** — Nginx web server serving static HTML fixtures,
  used by Behat tests that do not require Drupal (`@blackbox`
  tagged tests).
- **chrome** — Selenium standalone Chromium for browser testing.
- **proxy** — Traefik reverse proxy for HTTPS/TLS termination
  in CI.

### Choosing PHP and Drupal versions

Set the `PHP_VERSION` and `DRUPAL_VERSION` environment variables
before starting the containers. The extension supports:

- **PHP**: 8.2, 8.3, 8.4
- **Drupal**: 10, 11

For example, to test with PHP 8.3 and Drupal 11 (the defaults):
```shell
export PHP_VERSION=8.3
export DRUPAL_VERSION=11
```

Or to test with PHP 8.2 and Drupal 10:
```shell
export PHP_VERSION=8.2
export DRUPAL_VERSION=10
```

Before switching between PHP or Drupal versions, run
`ahoy reset` to remove containers, build files, and lock files.

### Starting the environment

The quickest way to build the full environment is:
```shell
ahoy build
```

This resets any previous build, starts containers, installs
dependencies for the selected Drupal version, provisions the
site, and prints environment info.

To run each step manually:
```shell
ahoy up         # Start Docker containers. Run this once per session.
ahoy assemble   # Install Composer dependencies. Run this when switching Drupal versions.
ahoy provision  # Install Drupal and configure. Run this when need to re-install.
```

### Accessing the sites

Once the environment is running, you can access the sites in
your browser:

- Drupal site: http://localhost:8888
- Blackbox fixtures: http://localhost:8889
- Selenium VNC: http://localhost:7900/?autoconnect=1&password=secret

To use different ports, set `DRUPAL_HOST_PORT` and
`BLACKBOX_HOST_PORT` environment variables before starting the
containers.

## Automated testing

Testing is performed automatically in Github Actions when a PR
is submitted.

```shell
ahoy lint                   # Check coding standards
ahoy lint-fix               # Fix coding standards

ahoy test-phpspec           # Run PHPSpec unit tests
ahoy test-phpunit           # Run PHPUnit tests
ahoy test-phpunit-coverage  # Run PHPUnit tests with coverage
ahoy test                   # Run all unit tests with coverage

ahoy test-bdd               # Run all Behat tests
ahoy test-bdd-coverage      # Run all Behat tests with coverage

ahoy test-bdd-blackbox      # Blackbox tests only
ahoy test-bdd-drupal        # Drupal API tests only
ahoy test-bdd-drupal-https  # HTTPS tests only
```

### How code coverage works

Coverage is collected using [pcov](https://github.com/krakjoe/pcov)
and the [dvdoug/behat-code-coverage](https://github.com/dvdoug/behat-code-coverage)
extension.

**PHPUnit coverage** is straightforward — pcov instruments the
`src/` directory during the test run and generates reports to
`.logs/coverage/phpunit/`.

**Behat coverage** has two layers because some tests run Behat
as a subprocess (negative tests that verify error messages):

1. The **main Behat run** collects coverage into
   `.logs/coverage/behat/` (HTML, Cobertura, and a serialized
   PHP coverage object).
2. **Subprocess Behat runs** (triggered by `BehatCliTrait` for
   `@behatcli` tagged scenarios) each write their own coverage
   file to `.logs/coverage/behat_cli/phpcov/` (the subprocess input
   directory). This only happens
   when pcov is enabled — the trait detects this via
   `ini_get('pcov.enabled')`.
3. After all tests complete, `scripts/merge-coverage.php` takes
   the main coverage and merges all subprocess coverage files
   into a combined report at `.logs/coverage/behat_merged/`.

The final merged report in `.logs/coverage/behat_merged/` is what
CI uses to check coverage thresholds.

## Debugging with Xdebug

### Debugging Drupal (browser requests)

Run `ahoy debug` to restart the PHP container with Xdebug enabled
in debug mode. Set breakpoints in your IDE and make a request
through the browser — Xdebug will connect automatically.

To disable Xdebug, run `ahoy up` to restart the container with
the default configuration.

### Debugging CLI commands (Behat, Drush, PHPUnit)

CLI commands run inside the container require additional PHP flags
to activate Xdebug, because the FPM Xdebug configuration does not
apply to CLI processes.

First, enable Xdebug with `ahoy debug`, then run your command
with the following flags:

```shell
ahoy cli "php -d xdebug.mode=debug -d xdebug.start_with_request=yes ./vendor/bin/behat --profile=drupal tests/behat/features/screenshot.feature"
```

The same approach works for any CLI tool:
```shell
ahoy cli "php -d xdebug.mode=debug -d xdebug.start_with_request=yes ./vendor/bin/drush --root=build/web status"
ahoy cli "php -d xdebug.mode=debug -d xdebug.start_with_request=yes ./vendor/bin/phpunit -c phpunit.xml"
```

Make sure your IDE is listening for incoming Xdebug connections
before running the command.

## Before submitting a change

- Check the changes from `composer require` are not included in
  your submitted PR.
- Before testing another PHP or Drupal version, run `ahoy reset`
  to remove containers, build files, and lock files.
- Run `ahoy lint` to check for coding standard violations.
- Run `ahoy lint-fix` to automatically fix coding standard
  violations.
