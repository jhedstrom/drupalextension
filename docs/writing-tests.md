# Writing tests

This page covers patterns that come up while writing scenarios with
the Drupal Extension. For the catalogue of step definitions, see
[`STEPS.md`](../STEPS.md).

## Layout

A typical project layout:

```
your-project/
├── behat.yml
├── tests/
│   ├── bootstrap/
│   │   └── FeatureContext.php
│   └── features/
│       ├── login.feature
│       └── content.feature
└── vendor/
```

`behat.yml` declares `paths: ['%paths.base%/tests/features']` and
`autoload: ['%paths.base%/tests/bootstrap']`. Each `.feature` file in
`tests/features/` contains one Feature with multiple Scenarios.

## Tagging scenarios

Tags select which scenarios run, which contexts apply, and which
driver Behat picks up.

| Tag | Effect |
| --- | --- |
| `@api` | Use the driver named under `api_driver` (Drush or Drupal API). Without `@api`, the Blackbox driver runs. |
| `@javascript` | Use a JavaScript-capable Mink session (requires Selenium or a similar driver). |
| Custom tags | Filter runs with `behat --tags=@critical`. |

```gherkin
@api
Feature: Article workflow

  @critical
  Scenario: Anonymous users see published articles
    Given the following "article" content:
      | title         | status |
      | Public update | 1      |
    When I am on the homepage
    Then I should see "Public update"
```

## Region steps

The region steps need a `regions` map. Configure one in `behat.yml`:

```yaml
Drupal\DrupalExtension:
  blackbox: ~
  regions:
    header: '#header'
    content: '#main'
    footer: '#footer'
```

Then reference regions by name in scenarios:

```gherkin
Scenario: Click a link in a region
  Given I am on the homepage
  When I click "About us" in the "footer" region
  Then I should see the heading "About"
```

See [Blackbox driver - Region steps](drivers/blackbox.md#region-steps)
for the full list.

## Random data

`RandomContext` substitutes tokens in step text and table cells with
random values. The same token reused inside one scenario resolves to
the same value, so two steps can reference the same generated string,
machine name, or integer without coordinating.

Add `Drupal\DrupalExtension\Context\RandomContext` to the suite's
`contexts` list in `behat.yml`. The context has no Drupal driver
dependency, so it works in both Drupal API suites and pure blackbox
suites.

### Token grammar

```
[?<name>:<type>[,<args>]]
   │      │       │
   │      │       └── type-specific args (length, range, ...)
   │      └────────── generator type
   └───────────────── identity / cache key (same name = same value in a scenario)
```

`name` is the *identity* - reuse it to get the same value back. `type`
selects the generator. `args` are passed to the generator. Both `type`
and `args` are optional; bare `[?title]` is equivalent to
`[?title:string,10]`.

```gherkin
@api
Scenario: Articles get unique titles
  Given I am viewing an "article" content with the title "[?title]"
  Then I should see the heading "[?title]"
```

### Built-in types

| Type           | Default         | Example                       | Output shape          |
| -------------- | --------------- | ----------------------------- | --------------------- |
| `string`       | length `10`     | `[?title:string,8]`           | `a1b2c3d4`            |
| `name`         | length `10`     | `[?label:name,6]`             | `Az9Bx2`              |
| `machine_name` | length `10`     | `[?slug:machine_name,8]`      | `ab_cd_e1`            |
| `int`          | `0..PHP_INT_MAX`| `[?age:int,18,65]`            | `42`                  |
| `email`        | -               | `[?contact:email]`            | `abcd1234@efgh.test`  |
| `uuid`         | -               | `[?id:uuid]`                  | `f47ac10b-58cc-4...`  |

`string`, `name`, and `machine_name` differ in case and allowed
characters: `string` is lowercase alphanumeric, `name` preserves the
underlying `Random::name()` casing, and `machine_name` adds underscores.

### Cache equivalence

Tokens that normalise to the same `(name, type, args)` triple share one
cached value within a scenario:

| Literal                  | Canonical key       |
| ------------------------ | ------------------- |
| `[?title]`               | `title:string:10`   |
| `[?title:string]`        | `title:string:10`   |
| `[?title:string,10]`     | `title:string:10`   |
| `<?title>` (legacy)      | `title:string:10`   |
| `[?title:string,8]`      | `title:string:8`    |
| `[?title:int]`           | `title:int:0,...`   |

So a feature mid-migration from `<?title>` to `[?title]` resolves both
literals to the same string, letting you swap one usage at a time.

### Custom types

Add types by extending `RandomContext` and overriding `generate()`:

```php
<?php

use Drupal\DrupalExtension\Context\RandomContext;

class CustomRandomContext extends RandomContext {

  protected function generate(string $type, array $args): string|int {
    return match ($type) {
      'phone' => '+1' . random_int(2000000000, 9999999999),
      default => parent::generate($type, $args),
    };
  }

}
```

Register `CustomRandomContext` in `behat.yml` instead of `RandomContext`,
then use `[?primary:phone]` in scenarios.

### Legacy `<?token>` syntax

The original `<?token>` form is still accepted but deprecated. Each
unique legacy literal triggers a one-time `[Deprecation]` notice on
`STDERR` pointing at the equivalent `[?token]` replacement. Suppress
notices with the `suppress_deprecations` configuration key or the
`BEHAT_DRUPALEXTENSION_SUPPRESS_DEPRECATIONS` environment variable -
see [Configuration](configuration.md).

## Custom step definitions

When the prebuilt steps do not cover a behaviour, write your own. Add
methods to `tests/bootstrap/FeatureContext.php` (or any class declared
in `contexts:`) and annotate them:

```php
<?php

use Drupal\DrupalExtension\Context\RawDrupalContext;

class FeatureContext extends RawDrupalContext {

  /**
   * @Then I should see the current user's email
   */
  public function assertUserEmail() {
    $user = $this->getUserManager()->getCurrentUser();
    $this->assertSession()->pageTextContains($user->mail);
  }

}
```

For larger projects, split steps across multiple custom contexts. See
[Contexts](contexts.md) for the wiring.

## Hooks

To alter entities before they are saved by `DrupalContext`, use the
`@beforeNodeCreate`, `@beforeTermCreate`, and `@beforeUserCreate`
hooks. See [Hooks](hooks.md) for examples.

## Running scenarios

Run every feature:

```shell
vendor/bin/behat
```

Run a single feature:

```shell
vendor/bin/behat tests/features/login.feature
```

Run scenarios filtered by tag:

```shell
vendor/bin/behat --tags=@critical
```

Run a specific profile defined in `behat.yml`:

```shell
vendor/bin/behat --profile=local
```

## Debugging tips

- `behat -di` prints every step definition Behat can see, with the
  source class. Use it to confirm a context is loaded.
- `behat --dry-run` parses every feature without executing the steps.
  Useful for spotting undefined steps.
- `then print last response` (a Mink step) dumps the last HTML response
  to the console.
- `then show last response` opens the last HTML response in a browser
  (requires `show_cmd` set in the `Drupal\MinkExtension` block).
