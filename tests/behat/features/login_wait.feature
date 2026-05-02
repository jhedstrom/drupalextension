Feature: Login wait
  As a developer
  I want to configure a post-login wait
  So that login succeeds on sites where the logged-in selector appears asynchronously

  @test-drupal @api
  Scenario: Assert login succeeds with "login_wait" set to 0
    Given I am logged in as a user with the "authenticated user" role
    Then I should see the link "My account"

  @test-drupal @api
  Scenario: Assert login succeeds with "login_wait" set to a positive value
    Given some behat configuration
    And scenario steps tagged with "@test-drupal @api":
      """
      Given I am logged in as a user with the "authenticated user" role
      Then I should see the link "My account"
      """
    When I run behat with drupal profile and "login_wait" set to "2"
    Then it should pass

  @test-drupal @api
  Scenario: Assert "login_wait" rejects negative values
    Given some behat configuration
    And scenario steps tagged with "@test-drupal @api":
      """
      Given I am logged in as a user with the "authenticated user" role
      """
    When I run behat with drupal profile and "login_wait" set to "-1"
    Then it should fail with:
      """
      The value -1 is too small for path
      """

  @test-drupal @api @javascript
  Scenario: Assert login succeeds with "login_wait" when logged-in class is delayed by JS
    Given I run drush "state:set behat_test.slow_login 1500"
    And I am logged in as a user with the "authenticated user" role
    Then I should see the link "My account"

  @test-drupal @api @javascript
  Scenario: Assert "login_wait" extends to the last-resort logout link lookup
    # Body class is stripped on every response and never re-added during the
    # test, forcing 'loggedIn()' to fall through to the third-resort logout
    # link check. The logout link itself is delayed by 800ms so the
    # synchronous lookup fails and the wait introduced by 'login_wait' must
    # poll the DOM until the link appears.
    Given the post-login wait is set to 3 seconds
    And I run drush "state:set behat_test.slow_login 60000"
    And I run drush "state:set behat_test.slow_logout_link 800"
    When I am logged in as a user with the "authenticated user" role
    Then I should see the link "My account"

  @test-drupal @api
  Scenario: Assert slow login state is cleaned up
    Given I run drush "state:set behat_test.slow_login 0"
    And I run drush "state:set behat_test.slow_logout_link 0"
