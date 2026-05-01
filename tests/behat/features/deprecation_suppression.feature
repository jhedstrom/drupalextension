Feature: Suppression of deprecation notices
  As a developer
  I want to silence the '[Deprecation]' notices that the extension writes to STDERR
  So that I can keep my CI logs quiet while migrating off deprecated config

  # Suppression has two inputs:
  #   - 'suppress_deprecations: true' under 'Drupal\DrupalExtension'
  #   - 'BEHAT_DRUPALEXTENSION_SUPPRESS_DEPRECATIONS' env var (overrides the
  #     config in either direction)
  # Both apply to the extension load layer ('DrupalExtension::loadParameters')
  # and to the context runtime layer ('DeprecationTrait::triggerDeprecation').

  # Extension load layer ('region_map' deprecation).

  @test-blackbox
  Scenario: Assert config suppresses load-layer deprecation
    Given some behat configuration
    And the behat configuration uses the deprecated region_map
    And the behat configuration suppresses deprecations
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
    And the output should not contain:
      """
      [Deprecation]
      """

  @test-blackbox
  Scenario: Assert env var suppresses load-layer deprecation
    Given some behat configuration
    And the behat configuration uses the deprecated region_map
    And the "BEHAT_DRUPALEXTENSION_SUPPRESS_DEPRECATIONS" environment variable is set to "1"
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
    And the output should not contain:
      """
      [Deprecation]
      """

  @test-blackbox
  Scenario: Assert env var "0" overrides config and forces emit on load layer
    Given some behat configuration
    And the behat configuration uses the deprecated region_map
    And the behat configuration suppresses deprecations
    And the "BEHAT_DRUPALEXTENSION_SUPPRESS_DEPRECATIONS" environment variable is set to "0"
    And scenario steps tagged with "@test-blackbox":
      """
      Given I am at "form_controls.html"
      Then I should see the button "Submit" in the "static content" region
      """
    When I run behat
    Then the output should contain:
      """
      [Deprecation] The "region_map" configuration key under "Drupal\DrupalExtension" is deprecated in drupal-extension:6.0.0 and removed from drupal-extension:6.1.0.
      """

  # Context runtime layer (deprecated message selectors via 'MessageContext').

  @test-drupal @api
  Scenario: Assert config suppresses runtime-layer deprecation
    Given some behat configuration
    And the behat configuration uses the deprecated message selectors
    And the behat configuration suppresses deprecations
    And scenario steps tagged with "@test-drupal @api":
      """
      Given I am on "/user/login"
      When I fill in "a fake user" for "Username"
      And I fill in "a fake password" for "Password"
      And I press "Log in"
      Then I should see the error message "Unrecognized username or password"
      """
    When I run behat with drupal profile
    Then it should pass with:
      """
      1 scenario (1 passed)
      """
    And the output should not contain:
      """
      [Deprecation]
      """

  @test-drupal @api
  Scenario: Assert env var suppresses runtime-layer deprecation
    Given some behat configuration
    And the behat configuration uses the deprecated message selectors
    And the "BEHAT_DRUPALEXTENSION_SUPPRESS_DEPRECATIONS" environment variable is set to "1"
    And scenario steps tagged with "@test-drupal @api":
      """
      Given I am on "/user/login"
      When I fill in "a fake user" for "Username"
      And I fill in "a fake password" for "Password"
      And I press "Log in"
      Then I should see the error message "Unrecognized username or password"
      """
    When I run behat with drupal profile
    Then it should pass with:
      """
      1 scenario (1 passed)
      """
    And the output should not contain:
      """
      [Deprecation]
      """
