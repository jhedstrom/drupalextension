Feature: BigPipe NOJS bypass

  As a developer
  I want the Drupal Extension to set the BigPipe NOJS cookie automatically
  So that authenticated-user assertions work against pages with BigPipe enabled

  # 'big_pipe' is installed by default via the 'behat_test' fixture, so each
  # scenario starts with BigPipe active. 'DrupalContext' detects a non-JS
  # driver and sets the 'big_pipe_nojs' cookie in 'BeforeScenario', then
  # re-applies it on every step so it survives login redirects.

  @test-drupal @api
  Scenario: Assert that BigPipe cookie is set on a non-JS driver
    When I am on the homepage
    Then the cookie "big_pipe_nojs" exists

  @test-drupal @api @behat-steps-skip:bigPipeBeforeScenario
  Scenario: Assert that BigPipe cookie is not set when scenario skip tag is used
    When I am on the homepage
    Then the cookie "big_pipe_nojs" does not exist

  @test-drupal @api
  Scenario: Assert that BigPipe cookie is preserved across user logins
    Given the following users:
      | name           | mail                       | roles         | status |
      | bigpipe_admin  | bigpipe_admin@example.com  | administrator | 1      |
    When I am on the homepage
    Then the cookie "big_pipe_nojs" exists
    When I am logged in as "bigpipe_admin"
    And I am on the homepage
    Then the cookie "big_pipe_nojs" exists

  @test-drupal @api @behat-steps-skip:bigPipeBeforeStep
  Scenario: Assert that BigPipe cookie is not preserved when step skip tag is used
    Given the following users:
      | name           | mail                       | roles         | status |
      | bigpipe_admin  | bigpipe_admin@example.com  | administrator | 1      |
    When I am on the homepage
    Then the cookie "big_pipe_nojs" exists
    # Logging in as a new user removes cookies; the BeforeStep hook would
    # re-set the cookie, but the skip tag suppresses it.
    When I am logged in as "bigpipe_admin"
    And I am on the homepage
    Then the cookie "big_pipe_nojs" does not exist

  @test-drupal @api
  Scenario: Assert that BigPipe cookie is not set when bypass is disabled via config
    Given some behat configuration
    And the behat configuration disables big_pipe bypass
    And scenario steps tagged with "@test-drupal @api":
      """
      Given I am on the homepage
      Then the cookie "big_pipe_nojs" does not exist
      """
    When I run behat with drupal profile
    Then it should pass
