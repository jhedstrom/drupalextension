# Contexts

Behat 3 supports multiple contexts per suite. The Drupal Extension
ships several context classes you can mix and match in your
`behat.yml`. Every context you want available in a suite must be
declared explicitly.

## Available contexts

| Context | Purpose |
| --- | --- |
| `Drupal\DrupalExtension\Context\RawDrupalContext` | Base class with Drupal and Mink session helpers but no step definitions. Extend this in your `FeatureContext`. |
| `Drupal\DrupalExtension\Context\DrupalContext` | Steps for users, nodes, taxonomy terms, and login flows. |
| `Drupal\DrupalExtension\Context\MinkContext` | Region-aware extensions over the Mink Extension's default steps. |
| `Drupal\DrupalExtension\Context\MarkupContext` | Low-level markup assertions - tags, classes, attributes. |
| `Drupal\DrupalExtension\Context\MessageContext` | Steps for Drupal status, error, and warning messages. |
| `Drupal\DrupalExtension\Context\BatchContext` | Steps for the Drupal Batch API and queue items. |
| `Drupal\DrupalExtension\Context\ConfigContext` | Steps that read and write Drupal configuration. |
| `Drupal\DrupalExtension\Context\DrushContext` | Steps that invoke arbitrary Drush commands. |
| `Drupal\DrupalExtension\Context\MailContext` | Steps that assert against the Drupal mail collector. |
| `Drupal\DrupalExtension\Context\RandomContext` | Transforms placeholders such as `<?title>` into random strings. Pure Behat context - no Drupal driver, no Mink session. |

For a complete reference of every step each context exposes, see
[`STEPS.md`](../STEPS.md).

## Declaring contexts

List each context under `suites` in `behat.yml`:

```yaml
default:
  suites:
    default:
      contexts:
        - FeatureContext
        - Drupal\DrupalExtension\Context\DrupalContext
        - Drupal\DrupalExtension\Context\MinkContext
        - Drupal\DrupalExtension\Context\MessageContext
        - CustomContext
```

In this configuration, scenarios have access to:

- Pre-built user, node, and term steps from `DrupalContext`.
- Region-aware Mink steps from `MinkContext`.
- Drupal message assertions from `MessageContext`.
- Steps you defined in `tests/bootstrap/FeatureContext.php`.
- Steps you defined in `CustomContext`.

They do **not** have access to `MarkupContext`, `BatchContext`,
`DrushContext`, or any context not declared in this list.

## RawDrupalContext

`RawDrupalContext` is the base class to extend in your custom contexts.
It exposes Drupal and Mink session helpers without adding any step
definitions, so it will not collide with the prebuilt contexts.

```php
<?php

use Drupal\DrupalExtension\Context\RawDrupalContext;

class CustomContext extends RawDrupalContext {

  /**
   * @Then I should see the current user's email
   */
  public function assertUserEmail() {
    $user = $this->getUserManager()->getCurrentUser();
    $this->assertSession()->pageTextContains($user->mail);
  }

}
```

## DrushContext

`DrushContext` exposes a single step:

```gherkin
Given I run drush "cache:rebuild"
```

It forwards the command through the alias or root configured under
`Drupal\DrupalExtension.drush` in `behat.yml`. See
[Drush driver](drivers/drush.md) for setup details.

## Sharing state between contexts

When you split steps across several contexts, you sometimes need to
read state set by another. Use Behat's `BeforeScenario` hook to gather
references to the contexts you depend on:

```php
<?php

use Behat\Behat\Hook\Scope\BeforeScenarioScope;
use Drupal\DrupalExtension\Context\DrupalContext;
use Drupal\DrupalExtension\Context\MinkContext;
use Drupal\DrupalExtension\Context\RawDrupalContext;

class CustomContext extends RawDrupalContext {

  protected DrupalContext $drupalContext;

  protected MinkContext $minkContext;

  /**
   * @BeforeScenario
   */
  public function gatherContexts(BeforeScenarioScope $scope) {
    $environment = $scope->getEnvironment();
    $this->drupalContext = $environment->getContext(DrupalContext::class);
    $this->minkContext = $environment->getContext(MinkContext::class);
  }

}
```

After `gatherContexts()` runs, your custom context can call any method
on the gathered contexts.

## Custom contexts

You can structure your project's step definitions across as many
custom contexts as you like. See Behat's
[contexts guide](https://docs.behat.org/en/latest/user_guide/context.html)
for the underlying mechanics.

## Mink-only contexts

You do not need to extend `RawDrupalContext` to read extension
parameters or resolve regions. A context that only needs to drive the
browser can extend `RawMinkContext` (or `MinkContext`) and pull in two
lightweight traits:

- `Drupal\DrupalExtension\ParametersTrait` exposes `getParameter()`,
  `getDrupalText()`, and `getDrupalSelector()`. It is the consumption
  point for parameter, text, and selector access from any context,
  regardless of whether it inherits from `RawDrupalContext`. The
  context must also implement
  `Drupal\DrupalExtension\ParametersAwareInterface` so the
  initializer knows to inject the parameter array.
- `Drupal\DrupalExtension\RegionTrait` exposes `getRegion()`, which
  resolves the human-readable region name through Mink's `region`
  selector. The selector is registered by the Drupal extension
  service container at compile time, so it is available before any
  context is instantiated.

```php
<?php

use Behat\MinkExtension\Context\RawMinkContext;
use Drupal\DrupalExtension\ParametersAwareInterface;
use Drupal\DrupalExtension\ParametersTrait;
use Drupal\DrupalExtension\RegionTrait;

class CustomMinkContext extends RawMinkContext implements ParametersAwareInterface {

  use ParametersTrait;
  use RegionTrait;

  /**
   * @Then I should see the configured log_out link in the :region region
   */
  public function logoutLinkInRegion(string $region): void {
    $log_out = $this->getDrupalText('log_out');
    $element = $this->getRegion($region);
    if (!$element->findLink($log_out)) {
      throw new \RuntimeException(sprintf('Log out link "%s" not in region "%s".', $log_out, $region));
    }
  }

}
```

The bundled `MinkContext`, `MarkupContext`, and `MessageContext`
follow this pattern - none of them inherit from `RawDrupalContext`.

`RandomContext` goes one step further: it implements
`Behat\Behat\Context\Context` directly and uses no Mink session at all.
A consumer can register it in any Behat suite, even one that does not
load `Drupal\MinkExtension`.
