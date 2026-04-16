Feature: Drag and drop
  As a developer
  I want to drag one element onto another
  So that I can test drag-and-drop interactions

  @test-blackbox @javascript
  Scenario: Assert "When I drag element :source onto element :target" passes
    Given I am at "drag_and_drop.html"
    When I drag element "#source" onto element "#target"
    Then I should see "Element was dropped"

  @test-blackbox @javascript
  Scenario: Assert "When I drag element :source onto element :target" fails for missing source
    Given some behat configuration
    And scenario steps tagged with "@test-blackbox @javascript":
      """
      Given I am at "drag_and_drop.html"
      When I drag element "#nonexistent" onto element "#target"
      """
    When I run behat
    Then it should fail with an error:
      """
      Source element with css selector "#nonexistent" not found.
      """

  @test-blackbox @javascript
  Scenario: Assert "When I drag element :source onto element :target" fails for missing target
    Given some behat configuration
    And scenario steps tagged with "@test-blackbox @javascript":
      """
      Given I am at "drag_and_drop.html"
      When I drag element "#source" onto element "#nonexistent"
      """
    When I run behat
    Then it should fail with an error:
      """
      Target element with css selector "#nonexistent" not found.
      """
