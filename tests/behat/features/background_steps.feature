# @gherkinlint-disable-rule no-background-with-single-scenario
Feature: DrupalContext with background steps
  As a developer
  I want to use Background steps with Scenario Outlines
  So that I can share common setup across multiple test examples

  Background:
    Given the following "tags" terms:
      | name    |
      | Tag one |
      | Tag two |

    And the following users:
      | name     |
      | User one |
      | User two |

    And the following "article" content:
      | title    |
      | Node one |
      | Node two |

  @test-drupal @api
  Scenario Outline:
    Given I am not logged in

    Examples:
      | user |
      | foo  |
      | bar  |
