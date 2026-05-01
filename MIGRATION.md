# Migration guide: 5.x → 6.0

Version 6.0 enforces a consistent set of step-definition conventions across
all bundled contexts. The conventions are documented in
[`CONTRIBUTING.md`](CONTRIBUTING.md#step-definition-conventions) and validated
by `scripts/docs.php`; CI fails on any violation.

This is a breaking change. Feature files and subclassed contexts that depend
on the 5.x step text or method names need to be updated.

## Step text

Update each occurrence in your `.feature` files.

| 5.x step text                                                              | 6.0 step text                                                                            |
| -------------------------------------------------------------------------- | ---------------------------------------------------------------------------------------- |
| `Given there is an item in the system queue:`                              | `Given the following item is in the system queue:`                                       |
| `Given I set ... with key X with values:`                                  | `Given I set ... with key X with the following values:`                                  |
| `Then drush output should contain X`                                       | `Then the drush output should contain X`                                                 |
| `Then drush output should match X`                                         | `Then the drush output should match X`                                                   |
| `Then drush output should not contain X`                                   | `Then the drush output should not contain X`                                             |
| `Then print last drush output`                                             | `When I print the last drush output` (re-categorised from `Then`)                        |
| `When Drupal sends a/an mail:`                                             | `When I send the following mail:` / `When I send the following email:`                  |
| `Then a/an mail has been sent:` (and variants with `to` / `subject`)       | `Then the following mail should have been sent:` (and variants)                          |
| `Then a/an new mail is sent:` (and variants)                               | `Then the following new mail should have been sent:` (and variants)                      |
| `Then N mail(s) have been sent` / `Then no mail has been sent`             | `Then there should be a total of N mail(s) sent` / `... no mail(s) sent`                 |
| `Then N new mail(s) is/are sent`                                           | `Then there should be a total of N new mail(s) sent`                                     |
| `Given users:`                                                             | `Given the following users:`                                                             |
| `Given :type content:`                                                     | `Given the following :type content:`                                                     |
| `Given :vocabulary terms:`                                                 | `Given the following :vocabulary terms:`                                                 |
| `Given I am viewing a/an :type:` (table form)                              | `Given I am viewing a/an :type with the following fields:`                               |
| `Given I am viewing a/an :type content:` (table form)                      | `Given I am viewing a/an :type content with the following fields:`                       |
| `Then I should be able to edit a/an :type`                                 | `Then I should be able to edit the :type`                                                |
| `Then I should be able to edit a/an :type content`                         | `Then I should be able to edit the :type content`                                        |
| `Then break` / `Then I break`                                              | `When break` / `When I break` (re-categorised from `Then`)                               |
| `Then I log out`                                                           | `When I log out` (re-categorised from `Then`)                                            |

## Field syntax

The inline syntax accepted by `parseEntityFields()` (the body of every
`Given the following :type content:` and `Given the following users:`
table) has been replaced. The new syntax has a single uniform escape
mechanism (double quotes) and detects compound mode by value form rather
than by the spacing of separators, removing the silent failures that
plagued the legacy syntax.

### Configuration

A new `field_parser` extension parameter selects the active parser:

```yaml
default:
  extensions:
    Drupal\DrupalExtension:
      field_parser: default   # one of: default | legacy
```

`default` is the default and uses the new parser. To opt back into the
legacy parser during migration:

```yaml
field_parser: legacy
```

Setting `legacy` emits a deprecation notice once per process. The legacy
parser is removed in 6.1; setting `field_parser: legacy` then will produce
a hard configuration error.

### Side-by-side syntax

| Pattern                           | Legacy syntax                                                       | Modern syntax                                                                            |
| --------------------------------- | ------------------------------------------------------------------- | ---------------------------------------------------------------------------------------- |
| Compound named single             | `country: BE - locality: Brussel`                                   | `country:"BE", locality:"Brussel"`                                                       |
| Compound named multi              | `country: BE - locality: Brussel, country: FR - locality: Paris`    | `country:"BE", locality:"Brussel"; country:"FR", locality:"Paris"`                       |
| Compound positional single (link) | `Link 1 - http://example.com`                                       | `title:"Link 1", uri:"http://example.com"` (positional gone, use named keys)             |
| Compound positional multi (link)  | `L1 - http://a, L2 - http://b`                                      | `title:"L1", uri:"http://a"; title:"L2", uri:"http://b"`                                 |
| Token at compound value position  | not expressible                                                     | `value:[relative:-1 week], end_value:[relative:+1 week]`                                 |
| Scalar containing `,`             | `"Tag, one"`                                                        | `"Tag, one"` (unchanged)                                                                 |
| Scalar containing ` - `           | `"Alpha - Bravo"` (workaround required)                             | `Alpha - Bravo` (no escape needed)                                                       |
| Scalar containing `;`             | `Hello; world`                                                      | `"Hello; world"` (new escape required)                                                   |
| Scalar containing literal `"`     | not expressible                                                     | `note:"He said \"hi\""`                                                                  |
| Scalar that looks like `key:value`| `port:8080` (silent compound risk if value present)                 | `port:8080` (unambiguous scalar)                                                         |

### Positional compound columns are no longer supported

The legacy syntax allowed compound values without column names, e.g.
`Link 1 - http://example.com`. The modern syntax requires every column
to be named. Common field types and their column names:

| Field type           | Column 1   | Column 2   | Column 3 |
| -------------------- | ---------- | ---------- | -------- |
| `link`               | `title`    | `uri`      | -        |
| `text_with_summary`  | `value`    | `summary`  | `format` |
| `daterange`          | `value`    | `end_value`| -        |
| `image` / `file`     | `target_id`| `alt`      | `title`  |

### Whitespace tolerance

Whitespace around `,`, `;` and `:` is ignored outside quoted strings, so
both forms are accepted:

```text
country:"BE",locality:"Brussel"
country : "BE" , locality : "Brussel"
```

Whitespace inside `"..."` is preserved literally.

### Escape sequences inside quoted strings

| Sequence | Decoded |
| -------- | ------- |
| `\"`     | `"`     |
| `\\`     | `\`     |
| `\n`     | LF      |
| `\t`     | TAB     |
| `\r`     | CR      |

Any other backslash sequence is a parse error.

### New parse-error format

The modern parser reports parse errors with character-level position
information. A typical failure looks like:

```text
Parse error in field_post_address:
country:"BE", locality:Brussel, postal_code:"1000"
              ^
unquoted_compound_value at offset 14: Compound column "locality" must use a quoted string or token.
Hint: Wrap the value in double quotes or use a [token:value] form.
```

All errors detected in a single cell are reported together, so authors
fix every problem in one edit instead of one error per run.

## Behaviour changes

| Step                | 5.x behaviour                                          | 6.0 behaviour                                                          |
| ------------------- | ------------------------------------------------------ | ---------------------------------------------------------------------- |
| `When I visit :path` | Implicitly asserted HTTP 200 on the response.          | Plain navigation. Use `Then I should get a :code HTTP response` to assert status. |

## Exception classes

6.0 narrows the exceptions thrown by bundled contexts. Generic `\Exception`
throws have been replaced with typed exceptions so callers can distinguish
"element not found" from "assertion failed" from "runtime error".

| Situation                                            | 5.x                | 6.0                                              |
|------------------------------------------------------|--------------------|--------------------------------------------------|
| Element / field / link / button / region not found   | `\Exception`       | `Behat\Mink\Exception\ElementNotFoundException`  |
| Assertion fails (value mismatch, state verification) | `\Exception`       | `Behat\Mink\Exception\ExpectationException`      |
| Configuration / processing error (non-assertion)     | `\Exception`       | `\RuntimeException`                              |

The auto-generated `ElementNotFoundException` messages are different from
the 5.x free-form text — for example
`No link to "About" on the page <url>` becomes
`Link with id|title|alt|text "About" not found.`. Update any feature
tests or `try`/`catch` blocks that rely on the 5.x message text.

See [`CONTRIBUTING.md`](CONTRIBUTING.md#exception-conventions) for the
full convention.

## Method renames

If you subclass any of the bundled contexts and override an `@Then` method,
rename your override. All `@Then` methods now follow `<concern>Assert<action>`
(concern first, then `Assert`, then the action) so the validator can
statically verify intent.

Examples:

- `MessageContext::assertErrorVisible` → `errorMessageAssertIsVisible`
- `MessageContext::assertNotMessage` → `messageAssertIsNotVisible`
- `MailContext::mailHasBeenSent` → `mailAssertHasBeenSent`
- `MailContext::noMailHasBeenSent` → `mailCountAssertEquals`
- `DrushContext::drushOutputShouldNotContain` → `drushOutputAssertNotContains`
- `MarkupContext::assertRegionElement` → `regionElementAssertExists`
- `MinkContext::assertHeading` → `headingAssertIsVisible`
- `MinkContext::assertButton` → `buttonAssertIsVisible`

Several multi-step methods have also been split, so that each step
annotation lives on its own method. See [`STEPS.md`](STEPS.md) for the full
list of methods and their step patterns.

## MessageContext base class and selector configuration

`MessageContext` no longer extends `RawDrupalContext`. It now extends
`RawMinkContext` directly and uses `DrupalParametersTrait`, so it can be
registered in a blackbox-only suite without booting Drupal.

If you subclass `MessageContext`, replace any `RawDrupalContext`-typed
references with `RawMinkContext` and `use DrupalParametersTrait;` in your
subclass.

### Nested grouping: `Drupal\DrupalExtension.selectors.messages:`

Group the four message selectors under a new nested `selectors.messages:`
map (still inside `Drupal\DrupalExtension`). Future selector groups
(e.g. `forms:`, `regions:`) can sit alongside `messages:` without
flattening the namespace. The keys are shortened: drop the redundant
`_selector` suffix and the `_message` infix from each name.

| Legacy flat key (5.x)        | New nested key                |
| ---------------------------- | ----------------------------- |
| `message_selector`           | `selectors.messages.default`  |
| `error_message_selector`     | `selectors.messages.error`    |
| `success_message_selector`   | `selectors.messages.success`  |
| `warning_message_selector`   | `selectors.messages.warning`  |

```yaml
default:
  extensions:
    Drupal\DrupalExtension:
      selectors:
        messages:
          default: '.messages'
          error:   '.messages--error'
          success: '.messages--status'
          warning: '.messages--warning'
        login_form_selector: 'form#user-login,form#user-login-form'
        logged_in_selector: 'body.logged-in,body.user-logged-in'
```

### Deprecation: legacy flat keys

Defining `message_selector`, `error_message_selector`,
`success_message_selector` and `warning_message_selector` as flat keys
under `Drupal\DrupalExtension.selectors:` is deprecated and will be
removed in 6.1. The flat form still works in 6.0 and emits a one-shot
deprecation notice on first use. Migrate by moving the four keys under
`Drupal\DrupalExtension.selectors.messages:` and renaming them as shown
in the table above. Other entries under
`Drupal\DrupalExtension.selectors:` (`login_form_selector`,
`logged_in_selector`) are unaffected.

## Configuration: `ajax_timeout`

`ajax_timeout` has moved from `Drupal\MinkExtension` to `Drupal\DrupalExtension`.
The Drupal Mink extension no longer extends the upstream Mink schema; all
custom configuration belongs to `Drupal\DrupalExtension`.

Update `behat.yml`:

```yaml
# 5.x
default:
  extensions:
    Drupal\MinkExtension:
      ajax_timeout: 5

# 6.0
default:
  extensions:
    Drupal\DrupalExtension:
      ajax_timeout: 5
```

Subclasses that read the value need to switch from
`$this->getMinkParameter('ajax_timeout')` to
`$this->getParameter('ajax_timeout')`. Reading the value requires the
context to use `Drupal\DrupalExtension\ParametersTrait` and implement
`Drupal\DrupalExtension\Context\ParametersAwareInterface` (the bundled
`MinkContext` already does).

## Parameters interface and trait renames

The interface, trait, and methods that expose `Drupal\DrupalExtension`
parameters to contexts have lost their `Drupal` prefix. The Drupal extension
is the only source of context parameters in 6.0, so the qualifier was
redundant.

| 5.x                                                              | 6.0                                                          |
|------------------------------------------------------------------|--------------------------------------------------------------|
| `Drupal\DrupalExtension\Context\DrupalParametersAwareInterface`  | `Drupal\DrupalExtension\Context\ParametersAwareInterface`    |
| `Drupal\DrupalExtension\DrupalParametersTrait`                   | `Drupal\DrupalExtension\ParametersTrait`                     |
| `setDrupalParameters(array $parameters): void`                   | `setParameters(array $parameters): void`                     |
| `getDrupalParameter(string $name): mixed`                        | `getParameter(string $name): mixed`                          |
| `protected array $drupalParameters`                              | `protected array $parameters`                                |

The trait helpers `getDrupalText()` and `getDrupalSelector()` keep their
names; they retrieve specific config keys (text strings, CSS selectors)
rather than arbitrary parameters and are unaffected.

Update any subclass or custom context that implements the interface,
uses the trait, or calls the renamed methods directly.

## Service interface changes

`DrupalMailManagerInterface::getMail()` and `::clearMail()` no longer accept
a `$store` argument. The v3 driver's `MailCapabilityInterface` exposes a
single implicit collector, so the multi-store concept inherited from
drupal-driver v2 has been removed.

| 5.x signature                              | 6.0 signature                  |
|--------------------------------------------|--------------------------------|
| `getMail(string $store)`                   | `getMail(): array`             |
| `clearMail(string $store): void`           | `clearMail(): void`            |

If you implement `DrupalMailManagerInterface` directly, drop the `$store`
parameter from your overrides. If you subclass `RawMailContext`, the
protected `getMail()` helper has lost its `$store` argument and the
`getMailMessageCount()` helper has been removed; the
`$mailMessageCount` property is now an `int` rather than an array keyed by
store name.

## Recommended upgrade flow

1. Update your `composer.json` to `drupal/drupal-extension:^6.0`.
2. Run `composer update drupal/drupal-extension`.
3. Run your Behat suite. Undefined-step errors will list every scenario that
   needs updating.
4. Apply the step-text changes from the table above to each affected
   `.feature` file.
5. If you subclass any bundled context, rename overridden `@Then` methods to
   the `<concern>Assert<action>` form.
6. Re-run your Behat suite.
