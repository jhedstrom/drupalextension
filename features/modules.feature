@api @d6 @d7 @d8
Feature: Module support
  In order to be able to test optional features
  As a developer for a Drupal distribution
  I need to be able to install and uninstall modules

  # These test scenarios assume to have a clean installation of the "standard"
  # profile and that the "behat_test" module from the "fixtures/" folder is
  # enabled on the site.

  Scenario: Enable module with dependencies
    # The Comment module is enabled in the standard install.
    Given the "comment" module should be active

    # Check that we can disable modules.
    When the "comment" module is disabled
    Then the "comment" module should not be active

    # The Forum module depends on the Comment module, so both should be enabled.
    Given the "forum" module is enabled
    Then the "forum" module should be active
    Then the "comment" module should be active

  Scenario: Enable and disable multiple modules at once
    Given the following modules are enabled:
    | module |
    | color  |
    | forum  |
    | search |
    Then the "color" module should be active
    And the "forum" module should be active
    And the "search" module should be active
    When the following modules are disabled:
    | module |
    | color  |
    | forum  |
    | search |
    Then the "color" module should not be active
    And the "forum" module should not be active
    And the "search" module should not be active
