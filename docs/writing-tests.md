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

`RandomContext` lets you reference random tokens in scenarios. The
same token used twice resolves to the same value within a single
scenario:

```gherkin
@api
Scenario: Articles get unique titles
  Given I am viewing an "article" content with the title "<?title>"
  Then I should see the heading "<?title>"
```

Add `Drupal\DrupalExtension\Context\RandomContext` to the
suite's `contexts` list in `behat.yml`.

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
