@api @d7 @d8
Feature: Language support
  In order to demonstrate the language integration
  As a developer for the Behat Extension
  I need to provide test cases for the language support

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
