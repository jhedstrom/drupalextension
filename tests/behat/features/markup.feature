Feature: MarkupContext
  As a developer
  I want to verify MarkupContext step definitions work correctly
  So that I can trust markup assertions and error messages

  @test-blackbox
  Scenario: Fail when element not found in region
    Given some behat configuration
    And scenario steps tagged with "@test-blackbox":
      """
      Given I am at "form_controls.html"
      Then I should see the "h99" element in the "static content" region
      """
    When I run behat
    Then it should fail with an error:
      """
      The element "h99" was not found in the "static content" region
      """

  @test-blackbox
  Scenario: Fail when text not found in element in region
    Given some behat configuration
    And scenario steps tagged with "@test-blackbox":
      """
      Given I am at "index.html"
      Then I should see "NONEXISTENT_TEXT" in the "h1" element in the "static left header" region
      """
    When I run behat
    Then it should fail with an error:
      """
      The text "NONEXISTENT_TEXT" was not found in the "h1" element in the "static left header" region
      """
