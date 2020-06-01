# Change log

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/)
and this project adheres to [Semantic Versioning](http://semver.org/).

## [Unreleased]
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

[Unreleased]: https://github.com/jhedstrom/drupalextension/compare/v4.1.0...HEAD
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
