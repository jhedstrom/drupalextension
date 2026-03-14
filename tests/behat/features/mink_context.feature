Feature: MinkContext coverage gaps
  As a developer
  I want comprehensive tests for MinkContext step definitions
  So that I can verify navigation, form interactions, and assertions work correctly

  @test-blackbox
  Scenario: Heading should not be present
    Given I am at "form_controls.html"
    Then I should not see the heading "Nonexistent Heading"

  @test-blackbox
  Scenario: Button should not be present
    Given I am at "form_controls.html"
    Then I should not see the button "Nonexistent Button"

  @test-blackbox
  Scenario: Check a checkbox
    Given I am at "form_controls.html"
    When I check the box "I agree to terms"

  @test-blackbox
  Scenario: Uncheck a checkbox
    Given I am at "form_controls.html"
    When I uncheck the box "Subscribe to newsletter"

  @test-blackbox
  Scenario: Uncheck a checkbox in a region
    Given I am at "form_controls.html"
    And I check "Footer checkbox" in the "static footer" region
    Then I uncheck "Footer checkbox" in the "static footer" region

  @test-blackbox
  Scenario: Select a radio button by label with id
    Given I am at "form_controls.html"
    When I select the radio button "Blue" with the id "color-blue"

  @test-blackbox
  Scenario: Select a radio button by label only
    Given I am at "form_controls.html"
    When I select the radio button "Green"

  @test-blackbox
  Scenario: Hidden link should not be visually visible
    Given I am at "form_controls.html"
    Then I should not visibly see the link "Hidden link"

  @test-blackbox
  Scenario: Should not get a specific HTTP response
    Given I am at "form_controls.html"
    Then I should not get a "404" HTTP response

  @test-blackbox
  Scenario: Enter a value for a field
    Given I am at "form_controls.html"
    When I enter "John" for "Name"

  @test-blackbox
  Scenario: For field enter value syntax
    Given I am at "form_controls.html"
    When for "Name" I enter "Jane"

  @test-blackbox
  Scenario: Fail when heading is present but should not be
    Given some behat configuration
    And scenario steps:
      """
      Given I am on "/user/login"
      Then I should not see the heading "Log in"
      """
    When I run "behat --no-colors"
    Then it should fail with an error:
      """
      The text 'Log in' was found in a heading
      """

  @test-blackbox
  Scenario: Fail when button is present but should not be
    Given some behat configuration
    And scenario steps:
      """
      Given I am on "/user/login"
      Then I should not see the button "Log in"
      """
    When I run "behat --no-colors"
    Then it should fail with an error:
      """
      The button 'Log in' was found on the page
      """

  @test-blackbox
  Scenario: Fail when link not found
    Given some behat configuration
    And scenario steps:
      """
      Given I am on "/user/login"
      Then I should see the link "Nonexistent link"
      """
    When I run "behat --no-colors"
    Then it should fail with an error:
      """
      No link to 'Nonexistent link'
      """

  @test-blackbox
  Scenario: Fail when heading not found
    Given some behat configuration
    And scenario steps:
      """
      Given I am on "/user/login"
      Then I should see the heading "Does Not Exist"
      """
    When I run "behat --no-colors"
    Then it should fail with an error:
      """
      The text 'Does Not Exist' was not found in any heading
      """

  @test-blackbox
  Scenario: Fail when button not found
    Given some behat configuration
    And scenario steps:
      """
      Given I am on "/user/login"
      Then I should see the button "Missing Button"
      """
    When I run "behat --no-colors"
    Then it should fail with an error:
      """
      The button 'Missing Button' was not found on the page
      """

  @test-blackbox
  Scenario: Fail when text not found in region
    Given some behat configuration
    And scenario steps:
      """
      Given I am on "/user/login"
      Then I should see "NONEXISTENT_TEXT_xyz" in the "main content" region
      """
    When I run "behat --no-colors"
    Then it should fail with an error:
      """
      The text 'NONEXISTENT_TEXT_xyz' was not found in the region 'main content'
      """

  @test-blackbox
  Scenario: Fail when region not found
    Given some behat configuration
    And scenario steps:
      """
      Given I am on "/user/login"
      Then I should see "something" in the "nonexistent_region" region
      """
    When I run "behat --no-colors"
    Then it should fail with a "InvalidArgumentException" exception:
      """
      The "nonexistent_region" region isn't configured!
      """

  @test-blackbox
  Scenario: Fail when link not found in region
    Given some behat configuration
    And scenario steps:
      """
      Given I am on "/user/login"
      Then I should see the link "Missing" in the "main content" region
      """
    When I run "behat --no-colors"
    Then it should fail with an error:
      """
      No link to "Missing" in the "main content" region
      """
