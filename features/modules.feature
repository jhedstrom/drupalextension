@api @d8
Feature: Ensure that test modules are automatically enabled in annotated scenarios
  In order to use special test modules during test scenarios
  Modules should be automatically enabled

  @with-module:migrate
  Scenario: Enabling a module
    Given I am logged in as a user with the "administer modules" permission
    When I visit "/admin/modules"
    Then the checkbox "modules[migrate][enable]" should be checked
