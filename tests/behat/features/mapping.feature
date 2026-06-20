Feature: mappings configuration
  As a developer
  I want to replace "{{ Key }}" tokens in steps with values configured under
  the "mappings" key in behat.yml
  So that paths and strings can be named once and reused across scenarios

  # Mappings are grouped only for organisation; tokens resolve by bare key.
  # The MappingContext transform runs against any step argument, so a token
  # works wherever a string is accepted, not just in path steps.

  @test-blackbox @mapping
  Scenario: Assert "{{ Key }}" resolves a configured path
    Given I am at "{{ Form Page }}"
    Then I should see the button "Submit" in the "static content" region

  @test-blackbox @mapping
  Scenario: Assert "{{ Key }}" ignores whitespace inside the braces
    Given I am at "{{Form Page}}"
    Then I should see the button "Submit" in the "static content" region

  @test-blackbox @mapping
  Scenario: Assert a token resolves inside a non-path argument
    Given I am at "form_controls.html"
    Then I should see the button "{{ Submit Button }}" in the "static content" region

  @test-blackbox @mapping
  Scenario: Assert an unknown mapping key fails the step
    Given some behat configuration
    And scenario steps tagged with "@test-blackbox @mapping":
      """
      Given I am at "index.html"
      Then I should see the text "{{ Undefined Key }}"
      """
    When I run behat
    Then it should fail with an exception:
      """
      No such mapping: Undefined Key
      """

  # The scenarios below exercise the same tokens through the Drupal API
  # driver, proving resolution is driver-agnostic - it works against a real
  # Drupal site, not only the static blackbox fixtures.

  @test-drupal @api @mapping
  Scenario: Assert "{{ Key }}" resolves a configured path in the Drupal profile
    Given I am at "{{ User Login }}"
    Then I should see the text "Log in"

  @test-drupal @api @mapping
  Scenario: Assert a token resolves in a table cell and in step text under Drupal
    Given I am viewing a page with the following fields:
      | title | {{ Mapped Title }} |
    Then I should see the text "{{ Mapped Title }}"
