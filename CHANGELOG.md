# Change log

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/)
and this project adheres to [Semantic Versioning](http://semver.org/).

## [Unreleased]
## [5.3.0]
### Added
 * [#776](https://github.com/jhedstrom/drupalextension/pull/776) - Added table header validation and improved error message in `assertValidMessageTable()`.
 * [#773](https://github.com/jhedstrom/drupalextension/pull/773) - Added `behat.dist.yml` with all configuration values pre-filled.
 * [#772](https://github.com/jhedstrom/drupalextension/pull/772) - Added parent term resolution in `termCreate()` with validation and tests.
 * [#767](https://github.com/jhedstrom/drupalextension/pull/767) - Added double-quote escaping to prevent compound separator splitting entity reference values.
 * [#765](https://github.com/jhedstrom/drupalextension/pull/765) - Added validation for non-existent entity fields in `parseEntityFields()`.
 * [#764](https://github.com/jhedstrom/drupalextension/pull/764) - Added custom `DocumentElement` to remove DrupalFinder dependency from extension boot.
 * [#762](https://github.com/jhedstrom/drupalextension/pull/762) - Added test for fresh `REQUEST_TIME` during cron and bumped drupal-driver to ^2.4.2.
 * [#744](https://github.com/jhedstrom/drupalextension/pull/744) - Added examples to tests and updated docs generator to post summary to CI.
 * [#743](https://github.com/jhedstrom/drupalextension/pull/743) - Added instructions for AI agents.
 * [#742](https://github.com/jhedstrom/drupalextension/pull/742) - Added steps documentation generator.
 * [#756](https://github.com/jhedstrom/drupalextension/pull/756) - Added more negative test coverage.
 * [#753](https://github.com/jhedstrom/drupalextension/pull/753) - Added tests for Managers.
 * [#740](https://github.com/jhedstrom/drupalextension/pull/740) - Added comprehensive test coverage for all Context classes.
### Changed
 * [#781](https://github.com/jhedstrom/drupalextension/pull/781) - Simplified code.
 * [#780](https://github.com/jhedstrom/drupalextension/pull/780) - Converted standard Behat annotations to PHP 8 attributes.
   > [!WARNING]
   > If you extend any of the bundled context classes and override step or hook
   > methods, you must switch your overrides from docblock annotations (`@Given`,
   > `@When`, `@Then`, `@BeforeScenario`, etc.) to PHP 8 attributes (`#[Given]`,
   > `#[When]`, `#[Then]`, `#[BeforeScenario]`, etc.). Behat resolves both the
   > parent attribute and the child annotation independently, which causes a
   > `RedundantStepException` for step definitions and duplicate execution for
   > hooks. To safely override a method, either re-declare the step/hook using
   > only the PHP 8 attribute on the child (and remove it from the parent by not
   > registering the parent context), or override the method without any
   > annotation or attribute to inherit the parent's definition.
 * [#779](https://github.com/jhedstrom/drupalextension/pull/779) - Changed `ConfigContext::setConfig()` visibility from public to protected.
 * [#771](https://github.com/jhedstrom/drupalextension/pull/771) - Reformatted configuration trees to use hierarchical indentation and added formatter fences.
 * [#768](https://github.com/jhedstrom/drupalextension/pull/768) - Removed Drupal 6 and 7 driver support.
 * [#763](https://github.com/jhedstrom/drupalextension/pull/763) - Always create fresh user in role-based login steps.
 * [#761](https://github.com/jhedstrom/drupalextension/pull/761) - Separated link assertion from click action.
 * [#760](https://github.com/jhedstrom/drupalextension/pull/760) - Removed Drupal 6 and 7 support remnants.
 * [#758](https://github.com/jhedstrom/drupalextension/pull/758) - Removed deprecated `ScenarioTagTrait` and `RawDrupalContext` magic property accessors.
 * [#757](https://github.com/jhedstrom/drupalextension/pull/757) - Switched coding standards to Drupal.
 * [#755](https://github.com/jhedstrom/drupalextension/pull/755) - Updated testing harness to support negative coverage.
 * [#751](https://github.com/jhedstrom/drupalextension/pull/751) - Increased coverage for Mink extension service container.
 * [#750](https://github.com/jhedstrom/drupalextension/pull/750) - Fixed coverage merge.
 * [#748](https://github.com/jhedstrom/drupalextension/pull/748) - Increased coverage for `DrupalDriverManager`.
 * [#747](https://github.com/jhedstrom/drupalextension/pull/747) - Renamed blackbox fixtures to generic names and updated region mappings.
 * [#746](https://github.com/jhedstrom/drupalextension/pull/746) - Merged `FeatureContextTrait` into `FeatureContext`.
 * [#745](https://github.com/jhedstrom/drupalextension/pull/745) - Replaced regex annotation with turnip in `BatchContext::iWaitForTheBatchJobToFinish()`.
 * [#741](https://github.com/jhedstrom/drupalextension/pull/741) - Added posting of the coverage as comments to the PR.
 * [#739](https://github.com/jhedstrom/drupalextension/pull/739) - Updated stack to support Ahoy command wrapper and added coverage support.
### Fixed
 * [#778](https://github.com/jhedstrom/drupalextension/pull/778) - Fixed `iWaitForAjaxToFinish()` crashing on unstarted session.
 * [#777](https://github.com/jhedstrom/drupalextension/pull/777) - Fixed stale config cache in `cleanConfig()` breaking change detection.
 * [#775](https://github.com/jhedstrom/drupalextension/pull/775) - Fixed config backup using overridden value instead of original.
 * [#774](https://github.com/jhedstrom/drupalextension/pull/774) - Fixed stale login state leaking between scenarios.
## [5.2.0]
### Added
 * [#594](https://github.com/jhedstrom/drupalextension/pull/594) - Added expanding/collapsing of `<details>` element.
 * [#636](https://github.com/jhedstrom/drupalextension/pull/636) - Added "press button in row" step.
 * [#591](https://github.com/jhedstrom/drupalextension/pull/591) - Added checkbox manipulation in regions.
 * [#685](https://github.com/jhedstrom/drupalextension/pull/685) - Allow adding extra entries to text and selectors in `behat.yml`.
 * [#705](https://github.com/jhedstrom/drupalextension/pull/705) - Added `assertRegionElementText()` step.
 * [#722](https://github.com/jhedstrom/drupalextension/pull/722) - Added screenshot taking helper.
 * [#668](https://github.com/jhedstrom/drupalextension/pull/668) - Made it possible to override how to get the log out link.
 * [#592](https://github.com/jhedstrom/drupalextension/pull/592) - Added AJAX wait on `attachFileToField()`.
### Changed
 * [#678](https://github.com/jhedstrom/drupalextension/pull/678) - Aligned package with Drupal core.
 * [#689](https://github.com/jhedstrom/drupalextension/pull/689) - Added Symfony 7 compatibility.
 * [#713](https://github.com/jhedstrom/drupalextension/pull/713) - Switched to use Drupal 11 by default.
 * [#708](https://github.com/jhedstrom/drupalextension/pull/708) - Added PHP 8.4 support.
 * [#699](https://github.com/jhedstrom/drupalextension/pull/699) - Removed PHP 7.4 and Drupal 9 support.
 * [#723](https://github.com/jhedstrom/drupalextension/pull/723) - Added PHPStan and Rector for static analysis.
 * [#702](https://github.com/jhedstrom/drupalextension/pull/702) - Added `ergebnis/composer-normalize`.
 * [#717](https://github.com/jhedstrom/drupalextension/pull/717) - Moved tests under `tests` directory.
### Fixed
 * [#681](https://github.com/jhedstrom/drupalextension/pull/681) - Fixed `DrupalAuthenticationManager::logout()`.
 * [#605](https://github.com/jhedstrom/drupalextension/pull/605) - Fixed `TableNode` namespace for `instanceof` check.
 * [#666](https://github.com/jhedstrom/drupalextension/pull/666) - Fixed `iWaitForAjaxToFinish` failing.
 * [#690](https://github.com/jhedstrom/drupalextension/pull/690) - Fixed PHP 8.4 deprecation with `str_getcsv`.
 * [#682](https://github.com/jhedstrom/drupalextension/pull/682) - Fixed PHP 8.4 deprecation.
 * [#707](https://github.com/jhedstrom/drupalextension/pull/707) - Fixed `RandomContext` not transforming `<?placeholder>` variables in `TableNode` arguments.
 * [#665](https://github.com/jhedstrom/drupalextension/pull/665) - Fixed reverse term removal order.
## [5.1.0]
### Added
 * [#677](https://github.com/jhedstrom/drupalextension/pull/677) - Drupal 11 support
## [5.0.0]
### Added
 * [#655](https://github.com/jhedstrom/drupalextension/pull/655) Configure Guzzle request options
## [5.0.0 rc1]
### Fixed
 * [#629](https://github.com/jhedstrom/drupalextension/pull/629) Fix upstream Drupal getText issue.
### Added
 * [#631](https://github.com/jhedstrom/drupalextension/pull/631) Add a helper for the getting of the login submit element.
## [5.0.0 alpha1]
### Added
 * [#620](https://github.com/jhedstrom/drupalextension/pull/620) Drupal 10 compatibility.
 * [#634](https://github.com/jhedstrom/drupalextension/pull/634) Document local testing for contribution.
### Changed
 * [#613](https://github.com/jhedstrom/drupalextension/pull/613) Test Drupal with Olivero theme.
 * [#620](https://github.com/jhedstrom/drupalextension/pull/620) Support for Drupal 7 and 8 is discontinued.
 * [#620](https://github.com/jhedstrom/drupalextension/pull/620) Testing pipeline moved to GitHib actions.
 * [#620](https://github.com/jhedstrom/drupalextension/pull/620) Drop Goutte Driver in favor of BrowserKit.
 * [#628](https://github.com/jhedstrom/drupalextension/pull/628) Use BrowserKit with Guzzle client.
## [4.2.0]
### Added
  * [#606](https://github.com/jhedstrom/drupalextension/pull/606) Added PHP 8.1 support.
### Changed
  * Removed Drupal 6 test fixtures
### Fixed
  * [#600](https://github.com/jhedstrom/drupalextension/issues/600) Removes whitespace in tags.
  * [#603](https://github.com/jhedstrom/drupalextension/pull/603) Test for jQuery.hasOwnProperty('active').
## [4.1.0]
### Added
  * [#488](https://github.com/jhedstrom/drupalextension/issues/488) Authenticate user in the backend bootstrap process on login.
### Changed
  * [#563](https://github.com/jhedstrom/drupalextension/issues/563) Test on PHP 7.1 through 7.3 (and use Drush 10), remove testing on PHP 7.0.
  * [#565](https://github.com/jhedstrom/drupalextension/issues/565) Improved PHPSpec coverage and changed type-hinting to use proper interfaces where necessary.
  * [#569](https://github.com/jhedstrom/drupalextension/pull/569) Drupal 9 support by allowing Symfony 4.
### Fixed
  * [#561](https://github.com/jhedstrom/drupalextension/pull/561) Add space around keyword to avoid incorrect matching.
## [4.0.1] 2019-10-08
### Fixed
  * [#552](https://github.com/jhedstrom/drupalextension/issue/552) Remove hard-coded symfony/event-dispatcher requirement.
### Changed
  * [#553](https://github.com/jhedstrom/drupalextension/pull/553) Remove testing on PHP 5.6 and Drupal 6
## [4.0.0] 2019-09-27
## [4.0.0 rc1] 2019-07-24
### Changed
  * [#528](https://github.com/jhedstrom/drupalextension/pull/528) Show a more helpful failure when running `@javascript`
    scenarios with incorrect configuration.
  * [#545](https://github.com/jhedstrom/drupalextension/issue/545) Remove Zombie JS testing on Travis.
  * [#518](https://github.com/jhedstrom/drupalextension/issue/518) Subcontexts are deprecated and will be removed in v4.1.0.
### Added
  * [#527](https://github.com/jhedstrom/drupalextension/pull/527) Provide a step to check that a button is not in a region.
  * [#543](https://github.com/jhedstrom/drupalextension/issue/543) Run gherkin-lint against feature files on Travis.
  * [#544](https://github.com/jhedstrom/drupalextension/issue/544) Added a 'Contributing' section to the README.
### Fixed
  * [#542](https://github.com/jhedstrom/drupalextension/pull/542) Fix issue with certain symfony 4 components being pulled in.
  * [#537](https://github.com/jhedstrom/drupalextension/pull/537) Remove usage of deprecated `SnippetAcceptingContext`.

## [4.0.0 beta2] 2018-12-19
### Added
  * [#514](https://github.com/jhedstrom/drupalextension/pull/514) Add a note about the need to remove the entries in behat.yml to use BEHAT_PARAMS.
  * [#504](https://github.com/jhedstrom/drupalextension/issues/504) Added Gherkin linting.
  * [#507](https://github.com/jhedstrom/drupalextension/pull/511) Test Drupal 7 on PHP 7.
  * [#516](https://github.com/jhedstrom/drupalextension/pull/516) Warn users when message table is not correctly formatted.
### Changed
  * [#510](https://github.com/jhedstrom/drupalextension/pull/510) Provide TagTrait to replace ScenarioTagTrait.
  * [#512](https://github.com/jhedstrom/drupalextension/pull/512) Start testing on PHP 7.2.
  * [#521](https://github.com/jhedstrom/drupalextension/pull/521) Updated tests to work with DrupalDriver string field handlers change.
### Fixed
  * [#522](https://github.com/jhedstrom/drupalextension/pull/522) Composer path changed on travis.
  * [#520](https://github.com/jhedstrom/drupalextension/pull/520) Removes patch applied to Features module that was committed.
  * [#502](https://github.com/jhedstrom/drupalextension/pull/502) RawDrupalContext::loggedIn() can return false positive.
  * [#507](https://github.com/jhedstrom/drupalextension/issues/507) PHP coding standards update.
  * [#499](https://github.com/jhedstrom/drupalextension/pull/499) Fix config context backup strategy.
## [4.0.0 beta1] 2018-04-17
### Added
  * [#479](https://github.com/jhedstrom/drupalextension/issues/479): Provide more verbose exception when AJAX fails.
  * [#482](https://github.com/jhedstrom/drupalextension/pull/481): Adds a RandomContext for generating random string
    replacements during scenarios.
### Fixed
  * [#484](https://github.com/jhedstrom/drupalextension/pull/484): Scenario-level tags are now properly processed for
    before/after hooks in the `MinkContext`.
## [4.0.0 alpha4] 2018-03-19
### Added
  * [#195](https://github.com/jhedstrom/drupalextension/issues/195): Support for testing emails sent from Drupal.
  * [#477](https://github.com/jhedstrom/drupalextension/pull/477): Adds a `composer test` script.
### Changed
  * Switches from `1.x` to `2.x` version of the DrupalDriver. This was necessary for the email testing in
    [#195](https://github.com/jhedstrom/drupalextension/issues/195).
### Fixed
  * [#464](https://github.com/jhedstrom/drupalextension/pull/464): Remove outdated `sudo` installation instructions.

## [4.0.0 alpha3] 2018-02-26
### Added
  * [#467](https://github.com/jhedstrom/drupalextension/pull/467): Allows
     AJAX timeout to be overridden via an `ajax_timeout` parameter in `behat.yml`.
     In order to utilize this parameter, `Drupal\MinkExtension` should be specified
     in the `extensions` portion of `behat.yml` rather than `Behat\MinkExtension`.
  * [#449](https://github.com/jhedstrom/drupalextension/pull/449) Translate steps to Spanish.

### Fixed
  * [#460](https://github.com/jhedstrom/drupalextension/pull/460): Fix links in documentation to `drush.org`.

### Changed
  * [#457](https://github.com/jhedstrom/drupalextension/pull/457): Drupal 8 is now tested with Drush 9.
  * [#462](https://github.com/jhedstrom/drupalextension/pull/462): The version of DrupalDriver is now pinned to a stable
    release instead of `dev-master`.

## [4.0.0 alpha2] 2017-12-14
### Added
  * [#448](https://github.com/jhedstrom/drupalextension/pull/448): Additional use of the fast logout method.
### Fixed
  * [#447](https://github.com/jhedstrom/drupalextension/pull/447): Only reset session if it is started during fast
    logout.

## [4.0.0 alpha1] 2017-12-06
### Added
  * [#268](https://github.com/jhedstrom/drupalextension/pull/268): Added a `BatchContext` for testing queue-runners and
    other batch-related functionality.
  * [#425](https://github.com/jhedstrom/drupalextension/pull/425): Adds an authentication manager.
  * [#427](https://github.com/jhedstrom/drupalextension/pull/427): Improves logout performance by directly resetting the
    browser session.
  * [#431](https://github.com/jhedstrom/drupalextension/pull/431): Adds French translation in `fr.xliff`.
  * [#438](https://github.com/jhedstrom/drupalextension/pull/438): Coding standards now enforced and tested.
  * [#440](https://github.com/jhedstrom/drupalextension/pull/440): Adds authors and maintainers to `README.md` and
    `composer.json`.

### Changed
  * [#428](https://github.com/jhedstrom/drupalextension/pull/428): Moves `getContext()` to `RawDrupalContext`.
    This was previously in `DrupalSubContextBase`.

### Deprecated
  * Direct access to `RawDrupalContext::$users` and `RawDrupalContext::$user` is deprecated and
    will be marked `protected` in future releases.

### Fixed
  * [#423](https://github.com/jhedstrom/drupalextension/issues/423): Fixed Symfony 3 incompatibility
  * [#429](https://github.com/jhedstrom/drupalextension/pull/429): AJAX fix for Drupal 7.
  * [#430](https://github.com/jhedstrom/drupalextension/pull/430): Fixes `assertRegionElementAttribute` issue when there
    is more than one element.
  * [#433](https://github.com/jhedstrom/drupalextension/pull/433): Fixes code block in documentation.
  * [#434](https://github.com/jhedstrom/drupalextension/pull/434): Undesirable PHP notice when exceptions are thrown.
  * [#437](https://github.com/jhedstrom/drupalextension/pull/437): Radio button selector fix.
  * [#439](https://github.com/jhedstrom/drupalextension/pull/439): Symfony 3 compatibility follow-up fix.

[Unreleased]: https://github.com/jhedstrom/drupalextension/compare/v5.3.0...HEAD
[5.3.0]: https://github.com/jhedstrom/drupalextension/compare/v5.2.0...v5.3.0
[5.2.0]: https://github.com/jhedstrom/drupalextension/compare/v5.1.0...v5.2.0
[5.1.0]: https://github.com/jhedstrom/drupalextension/compare/v5.0.0...v5.1.0
[5.0.0]: https://github.com/jhedstrom/drupalextension/compare/v5.0.0rc1...v5.0.0
[5.0.0 alpha1]: https://github.com/jhedstrom/drupalextension/compare/v4.2.0...v5.0.0alpha1
[4.2.0]: https://github.com/jhedstrom/drupalextension/compare/v4.1.0...v4.2.0
[4.1.0]: https://github.com/jhedstrom/drupalextension/compare/v4.0.1...v4.1.0
[4.0.1]: https://github.com/jhedstrom/drupalextension/compare/v4.0.0...v4.0.1
[4.0.0]: https://github.com/jhedstrom/drupalextension/compare/v4.0.0rc1...v4.0.0
[4.0.0 rc1]: https://github.com/jhedstrom/drupalextension/compare/v4.0.0beta2...v4.0.0rc1
[4.0.0 beta2]: https://github.com/jhedstrom/drupalextension/compare/v4.0.0beta1...v4.0.0beta2
[4.0.0 beta1]: https://github.com/jhedstrom/drupalextension/compare/v4.0.0alpha4...v4.0.0beta1
[4.0.0 alpha4]:https://github.com/jhedstrom/drupalextension/compare/v4.0.0alpha3...v4.0.0alpha4
[4.0.0 alpha3]:https://github.com/jhedstrom/drupalextension/compare/v4.0.0alpha2...v4.0.0alpha3
[4.0.0 alpha2]:https://github.com/jhedstrom/drupalextension/compare/v4.0.0alpha1...v4.0.0alpha2
[4.0.0 alpha1]:https://github.com/jhedstrom/drupalextension/compare/v3.4.0...v4.0.0alpha1
