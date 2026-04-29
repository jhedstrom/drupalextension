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
  used by Behat tests that do not require Drupal (`@test-blackbox`
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

ahoy test                   # Run all tests without coverage
ahoy test-unit              # Run PHPUnit tests
ahoy test-bdd               # Run all Behat tests (all profiles)
ahoy test-bdd-blackbox      # Blackbox tests only
ahoy test-bdd-drupal        # Drupal API tests only
ahoy test-bdd-drupal-https  # HTTPS tests only

ahoy test-coverage          # Run all tests with coverage and merge
ahoy test-unit-coverage     # Run PHPUnit tests with coverage
ahoy test-bdd-coverage      # Run all Behat tests with coverage
```

### Behat scenario tagging

Every Behat scenario must have a **profile-selection tag** to control which
Behat profile runs it. These tags use a `test-` prefix to distinguish them
from functional tags like `@api` (which enables the Drupal API driver):

| Tag              | Profile         | Environment                    |
|------------------|-----------------|--------------------------------|
| `@test-blackbox` | `default`       | Static HTML fixtures           |
| `@test-drupal`   | `drupal`        | Installed Drupal site with API |
| `@test-https`    | `drupal_https`  | HTTPS via Traefik proxy        |

Functional tags like `@api`, `@javascript`, and `@smoke` serve a different
purpose — they enable specific Behat functionality (e.g. the Drupal API driver
or a JavaScript browser session). A scenario can have both types:

```gherkin
@api @test-drupal
Scenario: Create content via API
  Given I am logged in as a user with the "administrator" role
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
   into a combined report at `.logs/coverage/merged/`.

The final merged report in `.logs/coverage/merged/` is what
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

## Writing tests

Every step definition must have both **positive** and **negative** tests.
A positive test verifies the step works when conditions are met. A negative
test verifies the step produces a clear error when conditions are not met.

### Positive tests

Positive tests are straightforward Behat scenarios that exercise the step
directly. Tag them with the appropriate profile-selection tag and any
functional tags the step requires.

Blackbox example (from `blackbox_smoke.feature`):

```gherkin
@test-blackbox
Scenario: Assert "Then I should see( the text) :text in the :region( region)"
  Given I am at "index.html"
  Then I should see the text "Welcome to the test site."
  And I should see the text "Page Two" in the "static right header" region
```

Drupal example (from `drupal_smoke.feature`):

```gherkin
@test-drupal @api
Scenario: Assert that Drupal driver can log in as an administrator user
  Given I am logged in as a user with the "administer site configuration" permissions
  When I go to "admin"
  And I save screenshot
```

### Negative tests

Negative tests verify that a step fails with the correct error message.
Because Behat cannot assert on its own failures within the same process,
negative tests run Behat **as a subprocess** using the BehatCli system
(see `tests/behat/features/behatcli/README.md` for details).

The subprocess uses a **copy of the real `behat.yml`** from the project
root, so it has access to all profiles, regions, selectors, and
contexts. This means negative tests run against the same configuration
as normal tests.

**Important:** Negative test scenarios need two layers of tags:

1. The **outer scenario** tag — controls which profile runs the test
   itself.
2. The **inner scenario steps** tag — controls which profile the
   subprocess uses.

#### Blackbox negative test

The outer scenario is tagged `@test-blackbox`. The inner steps are
tagged `@test-blackbox` so the subprocess runs with the default
(blackbox) profile. Use `When I run behat` to run with the default
profile.

From `blackbox_smoke.feature`:

```gherkin
@test-blackbox
Scenario: Negative: Assert "Then I should see the text" fails for non-existent text
  Given some copied behat configuration
  And scenario steps tagged with "@test-blackbox":
    """
    Given I am at "index.html"
    Then I should see the text "This text does not exist anywhere"
    """
  When I run behat
  Then it should fail with an error:
    """
    The text "This text does not exist anywhere" was not found anywhere in the text of the current page.
    """
```

#### Drupal negative test

The outer scenario is tagged `@test-drupal @api`. The inner steps are
tagged `@test-drupal @api` so the subprocess runs with the drupal
profile. Use `When I run behat with drupal profile` to run with the
drupal profile.

From `drupal_smoke.feature`:

```gherkin
@test-drupal @api
Scenario: Negative: Assert that Drupal driver fails when text is not found
  Given some copied behat configuration
  And scenario steps tagged with "@test-drupal @api":
    """
    Given I am logged in as a user with the "administer site configuration" permissions
    Then I should see the text "Non-existing text" in the "Non-exiting row" row
    """
  When I run behat with drupal profile
  Then it should fail with an error:
    """
    No rows found on the page
    """
```

### Negative test structure

Every negative test follows the same four-step pattern:

1. `Given some copied behat configuration` — copies the real
   `behat.yml` into the subprocess working directory.
2. `And scenario steps tagged with "<tags>":` — writes a stub
   feature file with the steps to test. The tags must match the
   profile the subprocess will use.
3. `When I run behat` or `When I run behat with <profile> profile`
   — runs Behat as a subprocess.
4. `Then it should fail with an error:` — asserts the subprocess
   failed with the expected error message. Use `it should fail with
   an exception:` for `RuntimeException` errors, or
   `it should fail with a "<ExceptionClass>" exception:` for other
   exception types.

### Assertion steps for negative tests

| Step | Use when |
|------|----------|
| `Then it should fail with an error:` | Step threw an assertion error (`Exception` or `Behat\Mink\Exception\*`). Rejects `RuntimeException`. |
| `Then it should fail with an exception:` | Step threw a `RuntimeException` (unexpected runtime error, not an assertion). |
| `Then it should fail with a "<Class>" exception:` | Step threw a specific exception class (e.g., `InvalidArgumentException`). |
| `Then it should fail` | Step failed (any reason, no message check). |

## Exception conventions

Step definitions and helper classes throw exceptions in a consistent,
typed way so callers can distinguish assertion failures from runtime
problems and so feature tests can assert on the failure mode without
parsing free-form messages.

| Exception                                         | When thrown                                          |
|---------------------------------------------------|------------------------------------------------------|
| `Behat\Mink\Exception\ElementNotFoundException`   | Element, field, link, button, or selector not found  |
| `Behat\Mink\Exception\ExpectationException`       | Assertion fails (value mismatch, state verification) |
| `Behat\Mink\Exception\UnsupportedDriverActionException` | Feature requires a specific driver (e.g. JavaScript) |
| `\RuntimeException`                               | Invalid input or processing error (not an assertion) |
| `\InvalidArgumentException`                       | Invalid argument value passed to an API method       |

`ElementNotFoundException` extends `ExpectationException`, so catching
`ExpectationException` covers both. The Mink classes auto-render the
URL and a snippet of the page in their `__toString()`, so do not embed
the URL in the message when an `ExpectationException`-based class is
used.

Example messages:

```text
Element matching css "#my-element" not found.
Link with id|title|alt|text "About us" not found.
Field with id|name|label|value|placeholder "Search" not found.
Region with name "footer" not found.
```

## Step definition conventions

`scripts/docs.php` validates step definitions against the conventions enforced
in 6.0. CI fails on any violation. Before pushing, run `ahoy docs` to
regenerate `STEPS.md` and surface any issues.

| Rule | Applies to |
|------|------------|
| `@Given` steps ending with `:` must contain `following`. | Table-form Givens |
| `@When` steps must contain `I` followed by a space. | All Whens |
| `@Then` steps must contain `should`. | All Thens |
| `@Then` steps must contain `the`, `a`, or `no`. | All Thens |
| `@Then` method names must contain `Assert`. | All Then methods |
| `@Then` method names must NOT contain `Should`. | All Then methods |
| Each method declares one step annotation. | All steps |
| Each step has an `@code/@endcode` example in the docblock. | All steps |
| Steps use turnip syntax instead of unnecessary regex. | All steps |

`@Then` method names follow `<concern>Assert<action>` (concern first, then
`Assert`, then action) so the validator can statically verify intent —
e.g. `errorMessageAssertIsVisible` rather than `assertErrorVisible`.

## Before submitting a change

- Check the changes from `composer require` are not included in
  your submitted PR.
- Before testing another PHP or Drupal version, run `ahoy reset`
  to remove containers, build files, and lock files.
- Run `ahoy lint` to check for coding standard violations.
- Run `ahoy lint-fix` to automatically fix coding standard
  violations.
- Run `ahoy docs` to regenerate `STEPS.md` and `README.md` whenever
  step definitions change. CI will fail if these files are out of date.
