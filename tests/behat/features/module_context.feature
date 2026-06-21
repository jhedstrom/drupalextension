Feature: Shipping contexts in a module
  As a developer
  I want a module-provided context to expose working steps
  So that I can compose its step definitions in my own suite

  @test-drupal @api
  Scenario: Assert a context shipped by a module provides a working step
    When I visit the module custom login page
    Then I should see "Custom log in"
