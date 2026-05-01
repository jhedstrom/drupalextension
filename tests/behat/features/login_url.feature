Feature: Login URL
  As a developer
  I want to configure custom login and logout URLs
  So that Behat can authenticate against sites that disable the default '/user' path

  @test-drupal @api
  Scenario: Assert login and logout succeed with default URLs
    Given I am logged in as a user with the "authenticated user" role
    Then I should be logged in on the backend
    When I log out via the logout url
    Then I should be logged out on the backend

  @test-drupal @api
  Scenario: Assert login and logout succeed with custom "login_url" and "logout_url"
    Given some behat configuration
    And scenario steps tagged with "@test-drupal @api":
      """
      Given I am logged in as a user with the "authenticated user" role
      Then I should be logged in on the backend
      When I log out via the logout url
      Then I should be logged out on the backend
      """
    When I run behat with drupal profile and config:
      """
      text:
        login_url: /custom-login
        logout_url: /custom-logout
        logout_confirm_url: /custom-logout
      """
    Then it should pass

  @test-drupal @api
  Scenario: Assert login fails when "login_url" points to a non-existent path
    Given some behat configuration
    And scenario steps tagged with "@test-drupal @api":
      """
      Given I am logged in as a user with the "authenticated user" role
      """
    When I run behat with drupal profile and config:
      """
      text:
        login_url: /does-not-exist
      """
    Then it should fail with:
      """
      Form field with id|name|label|value|placeholder "Username" not found.
      """
