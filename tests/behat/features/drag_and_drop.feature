Feature: Drag and drop
  As a developer
  I want to drag one element onto another
  So that I can test drag-and-drop interactions

  @test-blackbox @javascript
  Scenario: Assert "When I drag element :source onto element :target" passes for target A
    Given I am at "drag_and_drop.html"
    When I drag element "#source" onto element "#target-a"
    Then I should see "Dropped on target-a"
    And I should not see "Dropped on target-b"

  @test-blackbox @javascript
  Scenario: Assert "When I drag element :source onto element :target" passes for target B
    Given I am at "drag_and_drop.html"
    When I drag element "#source" onto element "#target-b"
    Then I should see "Dropped on target-b"
    And I should not see "Dropped on target-a"

  @test-blackbox
  Scenario: Assert "When I drag element :source onto element :target" fails for missing source
    Given some behat configuration
    And scenario steps tagged with "@test-blackbox":
      """
      Given I am at "drag_and_drop.html"
      When I drag element "#nonexistent" onto element "#target-a"
      """
    When I run behat
    Then it should fail with an error:
      """
      Source element with css selector "#nonexistent" not found.
      """

  @test-blackbox
  Scenario: Assert "When I drag element :source onto element :target" fails for missing target
    Given some behat configuration
    And scenario steps tagged with "@test-blackbox":
      """
      Given I am at "drag_and_drop.html"
      When I drag element "#source" onto element "#nonexistent"
      """
    When I run behat
    Then it should fail with an error:
      """
      Target element with css selector "#nonexistent" not found.
      """
