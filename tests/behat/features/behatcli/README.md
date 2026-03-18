# BehatCli — Subprocess test infrastructure

This directory contains self-tests for the BehatCli infrastructure
itself. Most contributors will not need to modify these files — they
test the test framework, not the Drupal Extension's step definitions.

For writing positive and negative tests for step definitions, see the
"Writing tests" section in `CONTRIBUTING.md`.

## How it works

Negative tests need to verify that a step **fails** with the correct
error message. Behat cannot assert on its own failures within the same
process, so negative tests run a second Behat instance as a CLI
subprocess and inspect its output.

The subprocess infrastructure has two parts:

### BehatCliContext

`tests/behat/bootstrap/BehatCliContext.php` — an upstream Behat context
(originally from the Behat project itself) that provides the core
subprocess mechanics:

- Creates a temporary working directory for each scenario.
- `When I run "behat ..."` — spawns a Behat subprocess in the temp dir.
- `Then it should pass` / `Then it should fail` — checks the exit code.
- `Then it should fail with:` — checks exit code and output message.
- `the output should contain:` — checks subprocess output.
- `a file named "..." with:` — writes arbitrary files to the temp dir.

This file should not be modified to keep it synced with upstream.

### BehatCliTrait

`tests/behat/bootstrap/BehatCliTrait.php` — project-specific additions
that build on BehatCliContext:

- `Given some behat configuration` — copies the real `behat.yml`
  from the project root into the subprocess working directory, replacing
  `%paths.base%` with the absolute project path so that autoload, feature
  paths, and extension directories resolve correctly. Tag filters are
  removed so the subprocess runs any stub feature regardless of profile
  tags. **This is the recommended step for negative tests** — it ensures
  the subprocess uses the same configuration as the real test runs.
- `Given scenario steps (tagged with "..."):` — writes a stub feature
  file with the provided steps and optional tags.
- `When I run behat` — runs the subprocess with the `default` (`blackbox) profile.
- `When I run behat with :profile profile` — runs the subprocess with a
  specific profile (e.g., `drupal`).
- `Then it should fail with an error:` — asserts the subprocess failed
  with an assertion error (rejects `RuntimeException`).
- `Then it should fail with an exception:` — asserts the subprocess
  failed with a `RuntimeException`.
- `Then it should fail with a :exception exception:` — asserts the
  subprocess failed with a specific exception class.
- `the output should not contain:` — asserts the subprocess output does
  not contain a string.

The trait also handles setup via `@BeforeScenario`:
- Copies `FeatureContext.php` into the subprocess working directory so
  that helper step definitions (like test exception throwers) are
  available.
- Copies fixture files from `tests/behat/fixtures/`.
- Sets `DRUPAL_FINDER_*` environment variables so the subprocess can
  locate the Drupal root.

## Relationship to behat.yml

The `Given some behat configuration` step copies the real
`behat.yml` from the project root. This means:

- Changes to `behat.yml` (regions, selectors, contexts, extensions)
  automatically apply to negative tests.
- The subprocess has access to all profiles (`default`, `drupal`,
  `drupal_https`).
- Use `When I run behat` for the default (blackbox) profile, or
  `When I run behat with drupal profile` for the drupal profile.

## Files in this directory

- `behatcli.feature` — self-tests for `BehatCliContext` (pass/fail
  detection, nested PyStrings, file creation).
- `behatcli_trait.feature` — self-tests for `BehatCliTrait` assertion
  steps (error vs exception detection, output checking, custom
  exception types).
- `behatcli_javascript.feature` — tests that JavaScript sessions work
  correctly across subprocess runs.
