@behatcli @blackbox
Feature: Behat CLI context Javascript steps

  Tests that JS sessions can be correctly started and ended when running
  multiple Behat runs through CLI.

  @javascript
  Scenario: Test @javascript session can be started for the scenario
    Given I visit "/index.html"
    And I save screenshot

#  @todo Fix hanging sub-process and uncomment.
#  Scenario: Test @javascript session can be started for an assertion
#    Given some behat configuration
#    And scenario steps tagged with "@javascript":
#      """
#      Given I visit "/index.html"
#      """
#    When I run "behat --no-colors"
#    Then it should pass
#
#  Scenario: Test @javascript session can be started for assertion in the second run
#    Given some behat configuration
#    And scenario steps tagged with "@javascript":
#      """
#      Given I visit "/index.html"
#      """
#    When I run "behat --no-colors"
#    Then it should pass
#
  @javascript
  Scenario: Test @javascript session can be started for the scenario in the third run
    Given I visit "/index.html"
