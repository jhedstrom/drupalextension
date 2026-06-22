# Upgrading from 5.x to 6.0

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
| `Given I should not see the error message ...`                             | `Then I should not see the error message ...` (re-categorised from `Given`)              |
| `Given I should not see the success message ...`                           | `Then I should not see the success message ...` (re-categorised from `Given`)            |
| `Given I should not see the warning message ...`                           | `Then I should not see the warning message ...` (re-categorised from `Given`)            |

Note: Behat treats `Given`, `When` and `Then` as interchangeable when matching
step text, so existing scenarios that still use the old keyword continue to
work. The right-hand column is the canonical form documented in `STEPS.md` and
shown by IDE autocomplete in 6.0.

## Method renames

Version 6.0 enforces that only `@Then` step methods may carry "Assert" in
their name. Any subclass that overrode the following `@Given` or `@When`
methods must be updated to use the new method name.

| Context          | 5.x method name                              | 6.0 method name                          |
| ---------------- | -------------------------------------------- | ---------------------------------------- |
| `DrupalContext`  | `assertAuthenticatedByRole`                  | `iAmLoggedInAsUserWithRole`              |
| `DrupalContext`  | `assertAuthenticatedByRoleShort`             | `iAmLoggedInAsRole`                      |
| `DrupalContext`  | `assertAuthenticatedByRoleWithGivenFields`   | `iAmLoggedInAsUserWithRoleAndFields`     |
| `DrupalContext`  | `assertLoggedInByName`                       | `iAmLoggedInAs`                          |
| `DrupalContext`  | `assertLoggedInWithPermissions`              | `iAmLoggedInAsUserWithPermissions`       |
| `DrupalContext`  | `assertClickInTableRow`                      | `iClickInTableRow`                       |
| `DrupalContext`  | `assertPressInTableRow`                      | `iPressInTableRow`                       |
| `DrupalContext`  | `assertCacheClear`                           | `clearCache`                             |
| `DrupalContext`  | `assertCron`                                 | `iRunCron`                               |
| `DrupalContext`  | `assertViewingNode`                          | `iAmViewingNodeWithFields`               |
| `DrupalContext`  | `assertViewingNodeContent`                   | `iAmViewingNodeContentWithFields`        |
| `DrushContext`   | `assertDrushCommand`                         | `iRunDrush`                              |
| `DrushContext`   | `assertDrushCommandWithArgument`             | `iRunDrushWithArguments`                 |
| `MessageContext` | `assertNotErrorVisible`                      | `errorMessageAssertIsNotVisible`         |
| `MessageContext` | `assertNotSuccessMessage`                    | `successMessageAssertIsNotVisible`       |
| `MessageContext` | `assertNotWarningMessage`                    | `warningMessageAssertIsNotVisible`       |

The step text bound to each method is unchanged (with the exception of the
three `MessageContext` keyword changes documented in the table above), so
feature files only need updating where they relied on the old `Given`
keyword for the negative-message steps.

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
| Compound value with leading `[`   | `value: [TEST] Body` (bracket literal)                             | `value:"[TEST] Body"` (quote the bracket)                                                |
| Scalar containing `,`             | `"Tag, one"`                                                        | `"Tag, one"` (unchanged)                                                                 |
| Scalar containing ` - `           | `"Alpha - Bravo"` (workaround required)                             | `Alpha - Bravo` (no escape needed)                                                       |
| Scalar containing `;`             | `Hello; world`                                                      | `"Hello; world"` (new escape required)                                                   |
| Scalar containing literal `"`     | `Hello <a href="x">y</a>` (works incidentally)                      | `Hello <a href="x">y</a>` (allowed; `"` is only structural at item start)                |
| Scalar that looks like `key:value`| `port:8080` (silent compound risk if value present)                 | `port:8080` (unambiguous scalar)                                                         |

### Bracketed values (test-data markers)

A compound column value that begins with `[` is parsed as the start of a
`[name:value]` token. That is what makes `value:[relative:-1 week]` work,
but it also means a literal marker such as `[TEST]`, `[DEV]` or `[QA]` at
the start of a value is read as a token, not as text. A marker followed by
prose (`[TEST] Some body text.`) is a token plus trailing characters, which
is a parse error.

The colon-then-bracket is also what tips the cell into compound mode:
writing `value: [TEST] ...` makes the parser treat the whole cell as
compound, so every column then has to be a quoted string or a token.

Wrap the value in double quotes so the bracket is treated literally:

```text
value: [TEST] Some body text.     # error: '[TEST]' parsed as a token
value:"[TEST] Some body text."    # ok: bracket is literal inside quotes
```

Bracketed test-data markers (`[TEST]`, `[DEV]`, `[QA]`) are a common Behat
convention, so this is a frequent upgrade snag. A value that is genuinely a
token (`[relative:-1 week]`) needs no quoting; a marker that is just text
does. In scalar (single-value) cells - without `key:` columns - a leading
marker is read as text and needs no quoting; the rule applies only to
compound columns.

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

A common, non-obvious cause of `unquoted_compound_value` is a *different*
column whose value begins with `[`. That leading bracket can tip the whole
cell into compound mode, after which a sibling value that was valid as
scalar text now needs quoting - so the caret can point at a column that
looks fine. If that happens, check whether another value starts with a
`[marker]` and quote it (see
[Bracketed values](#bracketed-values-test-data-markers)).

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

## BatchContext removed

`Drupal\DrupalExtension\Context\BatchContext` no longer exists. Its two
step definitions are now provided by `DrupalContext` via the new
`Drupal\DrupalExtension\Context\Traits\BatchTrait` trait.

Both steps are Drupal-specific - `Given I wait for the batch job to
finish` polls the `#updateprogress` element rendered by Drupal's
Batch API, and `Given the following item is in the system queue:`
writes directly to the `queue` table managed by Drupal's
`SystemQueue`. Hosting them on `DrupalContext` (which extends
`RawDrupalContext` and is only loaded with the API driver) keeps
`\Drupal::` calls inside a bootstrapped Drupal kernel, removing the
silent failures that occurred when the steps were invoked under the
Blackbox or Drush drivers.

Remove the `BatchContext` entry from your `behat.yml` suites:

```yaml
# 5.x
default:
  suites:
    default:
      contexts:
        - Drupal\DrupalExtension\Context\DrupalContext
        - Drupal\DrupalExtension\Context\BatchContext

# 6.0
default:
  suites:
    default:
      contexts:
        - Drupal\DrupalExtension\Context\DrupalContext
```

If your suite already registers `DrupalContext`, the two batch/queue
steps remain available without any feature-file changes. If you
registered `BatchContext` without `DrupalContext`, add `DrupalContext`
to the suite to keep the steps.

If you subclass `BatchContext` directly, change the parent to
`DrupalContext` (or extend `RawDrupalContext` and `use BatchTrait;`
yourself).

## RandomContext base class

`RandomContext` no longer extends `RawDrupalContext`. It now implements
`Behat\Behat\Context\Context` directly, so it can be registered in a suite
that does not load `Drupal\MinkExtension` or `Drupal\DrupalExtension`.

The class instantiates `Drupal\Component\Utility\Random` directly to
generate placeholder values; the previous indirection through
`getDriver()->getRandom()` is gone. `RandomContext` no longer calls
`getDriver()` or `getSession()`.

If you subclass `RandomContext`, the inherited `RawDrupalContext` helpers
(`getDriver()`, `getSession()`, `getParameter()`, `getDrupalText()`,
`getDrupalSelector()`) are no longer available. To keep them, change the
parent of your subclass to `RawDrupalContext` (or `RawMinkContext`)
explicitly. To compose only what you need, `use ParametersTrait;` together
with `implements ParametersAwareInterface` for parameter access without
booting Drupal or Mink.

Step definitions and the placeholder regex are unchanged.

## Configuration: `region_map` renamed to `regions`

The `region_map` configuration key under `Drupal\DrupalExtension` has been
renamed to `regions`. The structure is unchanged.

Update `behat.yml`:

```yaml
# 5.x and pre-rename 6.0
default:
  extensions:
    Drupal\DrupalExtension:
      region_map:
        Header: '#header'
        Content: '#main'

# 6.0
default:
  extensions:
    Drupal\DrupalExtension:
      regions:
        Header: '#header'
        Content: '#main'
```

`region_map` still works during the 6.0 cycle - it emits a one-shot
deprecation notice on extension load and is removed in 6.1. If both
keys are present, an entry under `regions` overrides the same key
under `region_map`. The merged map is exposed to contexts as
`getParameter('regions')` and to Mink's `region` selector as the
`drupal.regions` container parameter.

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
`Drupal\DrupalExtension\ParametersAwareInterface` (the bundled
`MinkContext` already does).

## Parameters interface and trait renames

The interface, trait, and methods that expose `Drupal\DrupalExtension`
parameters to contexts have lost their `Drupal` prefix. The Drupal extension
is the only source of context parameters in 6.0, so the qualifier was
redundant.

| 5.x                                                              | 6.0                                                          |
|------------------------------------------------------------------|--------------------------------------------------------------|
| `Drupal\DrupalExtension\Context\DrupalParametersAwareInterface`  | `Drupal\DrupalExtension\ParametersAwareInterface`            |
| `Drupal\DrupalExtension\DrupalParametersTrait`                   | `Drupal\DrupalExtension\ParametersTrait`                     |
| `setDrupalParameters(array $parameters): void`                   | `setParameters(array $parameters): void`                     |
| `getDrupalParameter(string $name): mixed`                        | `getParameter(string $name): mixed`                          |
| `protected array $drupalParameters`                              | `protected array $parameters`                                |

The trait helpers `getDrupalText()` and `getDrupalSelector()` keep their
names; they retrieve specific config keys (text strings, CSS selectors)
rather than arbitrary parameters and are unaffected.

Update any subclass or custom context that implements the interface,
uses the trait, or calls the renamed methods directly.

## Manager class moves

The driver and mail manager classes were squatting on the global `Drupal\`
namespace and have been moved under `Drupal\DrupalExtension\Manager\`
alongside the existing user and authentication managers. The driver manager
also loses the `Drupal` prefix - the driver manager is generic infrastructure
(it routes to whatever driver Behat is configured with) and is not itself
Drupal-specific. The mail manager, user manager, and authentication manager
keep their `Drupal` prefix because they manage Drupal-specific concerns and
the prefix leaves room for future non-Drupal managers under the same
namespace.

| 5.x                                       | 6.0                                                          |
|-------------------------------------------|--------------------------------------------------------------|
| `Drupal\DrupalDriverManager`              | `Drupal\DrupalExtension\Manager\DriverManager`               |
| `Drupal\DrupalDriverManagerInterface`     | `Drupal\DrupalExtension\Manager\DriverManagerInterface`      |
| `Drupal\DrupalMailManager`                | `Drupal\DrupalExtension\Manager\DrupalMailManager`           |
| `Drupal\DrupalMailManagerInterface`       | `Drupal\DrupalExtension\Manager\DrupalMailManagerInterface`  |

The `Drupal\DrupalExtension\Manager\DrupalUserManager`,
`DrupalUserManagerInterface`, `DrupalAuthenticationManager`,
`DrupalAuthenticationManagerInterface` and `FastLogoutInterface` classes
are unchanged.

Update any subclass, custom context, or test double that imports the moved
classes by their old fully-qualified name. If you implement
`DrupalMailManagerInterface` directly, also see
[Service interface changes](#service-interface-changes) for unrelated
method-signature breaks on that interface.

### Service container parameters

If you override the driver manager service class in your own Behat config,
the container parameter value changes accordingly:

| 5.x parameter value                               | 6.0 parameter value                                                 |
|---------------------------------------------------|---------------------------------------------------------------------|
| `drupal.drupal.class: Drupal\DrupalDriverManager` | `drupal.drupal.class: Drupal\DrupalExtension\Manager\DriverManager` |

The service ids (`drupal.drupal`, `drupal.authentication_manager`,
`drupal.user_manager`) are unchanged.

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

# Upgrading from 6.0 to 6.1

## Random token: legacy `<?name>` syntax removed

The `<?name>` random-token syntax, deprecated in 6.0, has been removed.
Use the typed `[?name:type,args]` form instead. A bare `[?name]` is
equivalent to the old `<?name>` - a `string` of length 10 - so the common
case is a one-character-per-token change.

| 6.0 (removed)    | 6.1              |
| ---------------- | ---------------- |
| `<?title>`       | `[?title]`       |
| `<?user>`        | `[?user]`        |
| `<?random_page>` | `[?random_page]` |

Update every `<?name>` token in your `.feature` files to `[?name]`. The
typed form also supports explicit generator types and arguments, such as
`[?slug:machine_name,8]` or `[?age:int,18,65]`; see
[Writing tests](docs/writing-tests.md#random-data) for the full list of
built-in types.

If you subclass `RandomContext`, note that its interface surface shrank: it
no longer implements `ParametersAwareInterface` or `DeprecationInterface`
and is now a bare `Behat\Behat\Context\Context`. The
`transformVariablesLegacy()` and `transformTableLegacy()` methods are gone;
`transformVariables()` / `transformTable()` handle the `[?...]` form. If
your subclass relied on the inherited `getParameter()` accessor, add
`use ParametersTrait;` together with `implements ParametersAwareInterface`
to restore it.

## `Before/AfterEntityCreate` hooks fan out to every entity create path

In 6.0 the `Before/AfterEntityCreateScope` hooks (registered via the
`#[BeforeEntityCreate]` / `#[AfterEntityCreate]` attributes) only fired
for the generic `Given the following :type entities:` step that
`entityCreate()` backs.

In 6.1 these scopes also fire as siblings of the per-type scopes inside
`nodeCreate()`, `userCreate()`, and `termCreate()`. A single
`#[BeforeEntityCreate]` / `#[AfterEntityCreate]` handler now observes
every entity create within a scenario - node, term, user, and generic -
making it the right tool for cross-cutting concerns like audit logging,
tagging, or scenario-wide telemetry.

The per-type scopes (`BeforeNodeCreateScope`, `BeforeTermCreateScope`,
`BeforeUserCreateScope` and their `After*` counterparts) are unchanged
and remain the correct choice for type-targeted handlers.

Dispatch order within each create path is per-type first, then entity:

```text
nodeCreate()    → BeforeNodeCreateScope    → BeforeEntityCreateScope    → save → AfterNodeCreateScope    → AfterEntityCreateScope
userCreate()    → BeforeUserCreateScope    → BeforeEntityCreateScope    → save → AfterUserCreateScope    → AfterEntityCreateScope
termCreate()    → BeforeTermCreateScope    → BeforeEntityCreateScope    → save → AfterTermCreateScope    → AfterEntityCreateScope
entityCreate()  →                             BeforeEntityCreateScope    → save →                            AfterEntityCreateScope
```

This is purely additive - no deprecations and no API breaks. If you
already registered an `#[BeforeEntityCreate]` or `#[AfterEntityCreate]`
handler against 6.0 expecting it to fire only for the generic step,
audit the handler before upgrading: it will now fire for every entity
create in the scenario.

## Message selector flat keys removed

The flat `message_selector`, `error_message_selector`,
`success_message_selector` and `warning_message_selector` keys under
`Drupal\DrupalExtension.selectors:` are removed. Configure the four
message selectors under the nested `selectors.messages:` map instead,
keyed by `default`, `error`, `success` and `warning`:

```yaml
Drupal\DrupalExtension:
  selectors:
    messages:
      default: '.messages'
      error: '.messages--error'
      success: '.messages--status'
      warning: '.messages--warning'
```

If a message selector is configured only under a removed flat key,
`MessageContext` throws a `RuntimeException` that names the nested key
to define. Other entries under `Drupal\DrupalExtension.selectors:`
(`login_form_selector`, `logged_in_selector`) are unaffected.
