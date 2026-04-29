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
