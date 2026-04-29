@smoke
Feature: Drupal driver smoke test

  As a DrupalExtension developer
  I want to verify that the Drupal driver can perform assertions
  So that I can ensure it is functioning correctly for basic use cases

  As a DrupalExtension developer
  I want to verify that the Drupal driver can produce coverage reports for positive and negative assertions
  So that I can ensure it is functioning correctly

  @test-drupal @api
  Scenario: Assert that Drupal driver can see text on the homepage
    Given I am on the homepage
    And I save screenshot

  @test-drupal @api
  Scenario: Assert that Drupal driver fails when text is not found on the homepage
    Given some behat configuration
    And scenario steps tagged with "@test-drupal @api":
      """
      Given I am logged in as a user with the "administer site configuration, access administration pages" permissions
      Then I should see the text "Non-existing text" in the "Non-exiting row" row
      """
    When I run behat with drupal profile
    Then it should fail with an error:
      """
      Row matching css "tr" not found.
      """

  @test-drupal @api
  Scenario: Assert that the Drupal driver can log in as an administrator user
    Given I am logged in as a user with the "administer site configuration, access administration pages" permissions
    When I go to "admin"
    And I save screenshot

  @test-drupal @api @javascript
  Scenario: Assert that the Drupal driver can log in as an administrator user using a real browser
    Given I am logged in as a user with the "administer site configuration, access administration pages" permissions
    When I go to "admin"
    And I save screenshot
