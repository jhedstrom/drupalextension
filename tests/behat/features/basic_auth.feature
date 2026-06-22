Feature: Basic authentication
  As a developer
  I want HTTP Basic auth credentials to persist across session resets
  So that I can test sites that sit behind webserver-level basic auth

  @test-blackbox
  Scenario: Assert base_url basic auth authenticates and persists across a session reset
    Given some behat configuration
    And the behat configuration uses base url basic auth with username "behatuser" and password "behatpass"
    And scenario steps tagged with "@test-blackbox":
      """
      Given I am on "/basic-auth.php"
      Then I should see "Authenticated as behatuser"
      When I am an anonymous user
      And I am on "/basic-auth.php"
      Then I should see "Authenticated as behatuser"
      """
    When I run behat
    Then it should pass

  @test-blackbox
  Scenario: Assert basic-auth-protected pages are rejected without credentials
    Given some behat configuration
    And scenario steps tagged with "@test-blackbox":
      """
      Given I am on "/basic-auth.php"
      Then I should see "Authenticated"
      """
    When I run behat
    Then it should fail with an error:
      """
      was not found anywhere in the text of the current page
      """

  @test-blackbox @javascript
  Scenario: Assert a JavaScript-driven browser authenticates from base_url userinfo
    When I visit "/basic-auth.php" with basic auth username "behatuser" and password "behatpass"
    Then I should see "Authenticated as behatuser"
