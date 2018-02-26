# Change log

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/)
and this project adheres to [Semantic Versioning](http://semver.org/).

## [Unreleased]

### Added
  * [#467](https://github.com/jhedstrom/drupalextension/pull/467): Allows
     AJAX timeout to be overridden via an `ajax_timeout` parameter in `behat.yml`.
     In order to utilize this parameter, `Drupal\MinkExtension` should be specified
     in the `extensions` portion of `behat.yml` rather than `Behat\MinkExtension`.

[Unreleased]: https://github.com/jhedstrom/drupalextension/compare/v4.0.0alpha2...HEAD
