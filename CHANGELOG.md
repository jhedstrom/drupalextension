# Change log

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/)
and this project adheres to [Semantic Versioning](http://semver.org/).

## [Unreleased]
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

[Unreleased]: https://github.com/jhedstrom/drupalextension/compare/v4.0.0beta1...HEAD
[4.0.0 beta1]: https://github.com/jhedstrom/drupalextension/compare/v4.0.0alpha4...v4.0.0beta1
[4.0.0 alpha4]:https://github.com/jhedstrom/drupalextension/compare/v4.0.0alpha3...v4.0.0alpha4
[4.0.0 alpha3]:https://github.com/jhedstrom/drupalextension/compare/v4.0.0alpha2...v4.0.0alpha3
[4.0.0 alpha2]:https://github.com/jhedstrom/drupalextension/compare/v4.0.0alpha1...v4.0.0alpha2
[4.0.0 alpha1]:https://github.com/jhedstrom/drupalextension/compare/v3.4.0...v4.0.0alpha1
