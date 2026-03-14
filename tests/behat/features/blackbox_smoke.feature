@smoke @test-blackbox
Feature: Blackbox driver smoke test

  As a visitor
  I want to interact with page regions and elements using the blackbox driver
  So that I can test UI behaviour without direct Drupal API access

  Scenario: Test the ability to find a heading in a region
    Given I am at "index.html"
    And I save screenshot
    Then I should see the text "Welcome to the test site."
    And I should see the text "Page Two" in the "static right header" region

  @javascript
  Scenario: Test the ability to find a heading in a region using a real browser
    Given I am at "index.html"
    And I save screenshot
    Then I should see the text "Welcome to the test site."
    And I should see the text "Page Two" in the "static right header" region
