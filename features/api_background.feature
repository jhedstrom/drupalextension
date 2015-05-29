@api
Feature: DrupalContextBackground
  Test DrupalContext in combination with Backgrounds

  Background:
    Given "tags" terms:
    | name    |
    | Tag one |
    | Tag two |

  Scenario:
    Given I am not logged in

  Scenario:
    Given I am not logged in
