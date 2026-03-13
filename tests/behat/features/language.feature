@api @test-drupal
Feature: Language support
  As a developer
  I want to enable and verify multiple languages
  So that I can test multilingual site functionality

  # These test scenarios assume to have a clean installation of the "standard"
  # profile and that the "behat_test" module from the "fixtures/" folder is
  # enabled on the site.

  Scenario: Enable multiple languages
    Given the following languages are available:
      | languages |
      | en        |
      | fr        |
      | de        |
    And I am logged in as a user with the 'administrator' role
    When I go to "admin/config/regional/language"
    Then I should see "English"
    And I should see "French"
    And I should see "German"
