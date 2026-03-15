@smoke
Feature: Blackbox driver smoke test

  As a DrupalExtension developer
  I want to verify that the Blackbox driver can perform assertions
  So that I can ensure it is functioning correctly

  As a DrupalExtension developer
  I want to verify that the Blackbox driver can produce coverage reports for positive and negative assertions
  So that I can ensure it is functioning correctly

  @test-blackbox
  Scenario: Assert "Then I should see( the text) :text in the :region( region)"
    Given I am at "index.html"
    And I save screenshot
    Then I should see the text "Welcome to the test site."
    And I should see the text "Page Two" in the "static right header" region

  @test-blackbox
  Scenario: Negative: Assert "Then I should see( the text) :text in the :region( region)" fails for non-existent text
    Given some behat configuration
    And scenario steps tagged with "@test-blackbox":
      """
      Given I am at "index.html"
      Then I should see the text "This text does not exist anywhere on the page"
      """
    When I run behat
    Then it should fail with an error:
      """
      The text "This text does not exist anywhere on the page" was not found anywhere in the text of the current page.
      """

  @test-blackbox @javascript
  Scenario: Assert "Then I should see( the text) :text in the :region( region)" using a real browser
    Given I am at "index.html"
    And I save screenshot
    Then I should see the text "Welcome to the test site."
    And I should see the text "Page Two" in the "static right header" region
