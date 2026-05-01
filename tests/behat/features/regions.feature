Feature: regions configuration
  As a developer
  I want region steps to resolve names from either the new 'regions' or
  the deprecated 'region_map' configuration key
  So that existing projects keep working while migrating off 'region_map'

  # The 'regions' key is exercised by every region step in the suite (see
  # markup.feature, mink.feature). The two scenarios below cover the
  # backward-compat path: 'region_map:' is renamed in the subprocess
  # config, the region step still resolves, and a deprecation notice is
  # emitted exactly once.

  @test-blackbox
  Scenario: Assert deprecated 'region_map' key still resolves regions
    Given some behat configuration
    And the behat configuration uses the deprecated region_map
    And scenario steps tagged with "@test-blackbox":
      """
      Given I am at "form_controls.html"
      Then I should see the button "Submit" in the "static content" region
      """
    When I run behat
    Then it should pass with:
      """
      1 scenario (1 passed)
      """

  @test-blackbox
  Scenario: Assert deprecated 'region_map' key emits deprecation notice
    Given some behat configuration
    And the behat configuration uses the deprecated region_map
    And scenario steps tagged with "@test-blackbox":
      """
      Given I am at "form_controls.html"
      Then I should see the button "Submit" in the "static content" region
      """
    When I run behat
    Then the output should contain:
      """
      [Deprecation] The "region_map" configuration key under "Drupal\DrupalExtension" is deprecated in drupal-extension:6.0.0 and removed from drupal-extension:6.1.0. Rename it to "regions". See https://github.com/jhedstrom/drupalextension/blob/main/MIGRATION.md
      """
