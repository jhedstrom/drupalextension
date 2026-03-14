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
  Scenario: Click a link
    Given I am at "form_controls.html"
    When I click "Visible link"
    Then I should see the text "Test Static Site"

  @test-blackbox
  Scenario: Press the button
    Given I am at "form_controls.html"
    When I press the "Submit" button

  @test-blackbox
  Scenario: See a link on the page
    Given I am at "form_controls.html"
    Then I should see the link "Visible link"

  @test-blackbox
  Scenario: Not see a link on the page
    Given I am at "form_controls.html"
    Then I should not see the link "Nonexistent link"

  @test-blackbox
  Scenario: Check a checkbox in a region
    Given I am at "form_controls.html"
    When I check "Footer checkbox" in the "static footer" region

  @test-blackbox
  Scenario: Should not see text on the page
    Given I am at "form_controls.html"
    Then I should not see the text "This text does not appear anywhere"

  @test-blackbox
  Scenario: Should get a 200 HTTP response
    Given I am at "form_controls.html"
    Then I should get a "200" HTTP response

  @test-blackbox
  Scenario: Fail when clicking a non-existent link
    Given some behat configuration
    And scenario steps:
      """
      Given I am on "/user/login"
      When I click "Nonexistent link xyz"
      """
    When I run "behat --no-colors"
    Then it should fail

  @test-blackbox
  Scenario: Fail when pressing a non-existent button
    Given some behat configuration
    And scenario steps:
      """
      Given I am on "/user/login"
      When I press the "Nonexistent Button" button
      """
    When I run "behat --no-colors"
    Then it should fail

  @test-blackbox
  Scenario: Fail when pressing non-existent button in region
    Given some behat configuration
    And scenario steps:
      """
      Given I am on "/user/login"
      Then I press "Nonexistent button" in the "main content" region
      """
    When I run "behat --no-colors"
    Then it should fail with an error:
      """
      The button 'Nonexistent button' was not found in the region 'main content'
      """

  @test-blackbox
  Scenario: Fail when heading not found in region
    Given some behat configuration
    And scenario steps:
      """
      Given I am on "/user/login"
      Then I should see the heading "Missing Heading" in the "main content" region
      """
    When I run "behat --no-colors"
    Then it should fail with an error:
      """
      The heading "Missing Heading" was not found in the "main content" region
      """

  @test-blackbox
  Scenario: Fail when link is present in region but should not be
    Given some behat configuration
    And scenario steps:
      """
      Given I am on "/user/login"
      Then I should not see the link "Log in" in the "main content" region
      """
    When I run "behat --no-colors"
    Then it should fail with an error:
      """
      Link to "Log in" in the "main content" region
      """

  @test-blackbox
  Scenario: Fail when text is present in region but should not be
    Given some behat configuration
    And scenario steps:
      """
      Given I am on "/user/login"
      Then I should not see "Username" in the "main content" region
      """
    When I run "behat --no-colors"
    Then it should fail with an error:
      """
      The text "Username" was found in the region "main content"
      """

  @test-blackbox
  Scenario: Fail when text not found on page
    Given some behat configuration
    And scenario steps:
      """
      Given I am on "/user/login"
      Then I should see the text "NONEXISTENT_TEXT_xyz_12345"
      """
    When I run "behat --no-colors"
    Then it should fail

  @test-blackbox
  Scenario: Fail when text is present but should not be on page
    Given some behat configuration
    And scenario steps:
      """
      Given I am on "/user/login"
      Then I should not see the text "Username"
      """
    When I run "behat --no-colors"
    Then it should fail

  @test-blackbox
  Scenario: Fail when HTTP response code is wrong
    Given some behat configuration
    And scenario steps:
      """
      Given I am on "/user/login"
      Then I should get a "404" HTTP response
      """
    When I run "behat --no-colors"
    Then it should fail

  @test-blackbox
  Scenario: Fail when HTTP response code matches but should not
    Given some behat configuration
    And scenario steps:
      """
      Given I am on "/user/login"
      Then I should not get a "200" HTTP response
      """
    When I run "behat --no-colors"
    Then it should fail

  @test-blackbox
  Scenario: Fail when selecting non-existent radio button
    Given some behat configuration
    And scenario steps:
      """
      Given I am on "/user/login"
      When I select the radio button "Nonexistent option"
      """
    When I run "behat --no-colors"
    Then it should fail with an error:
      """
      The radio button with "Nonexistent option" was not found
      """

  @test-blackbox
  Scenario: Fail when expanding non-existent details element
    Given some behat configuration
    And scenario steps:
      """
      Given I am on "/user/login"
      When I expand details labelled "Nonexistent details"
      """
    When I run "behat --no-colors"
    Then it should fail with an error:
      """
      Unable to find details
      """

  @test-blackbox
  Scenario: Fail when link not visible on page
    Given some behat configuration
    And scenario steps:
      """
      Given I am on "/user/login"
      Then I should see the link "Nonexistent link xyz"
      """
    When I run "behat --no-colors"
    Then it should fail with an error:
      """
      No link to 'Nonexistent link xyz'
      """

  @test-blackbox
  Scenario: Fail when link is present but should not be on page
    Given some behat configuration
    And scenario steps:
      """
      Given I am on "/user/login"
      Then I should not see the link "Log in"
      """
    When I run "behat --no-colors"
    Then it should fail with an error:
      """
      The link 'Log in' was present
      """

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
