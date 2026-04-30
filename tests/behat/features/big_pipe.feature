Feature: BigPipe NOJS bypass

  As a developer
  I want to opt scenarios into the BigPipe NOJS bypass via a tag
  So that authenticated-user assertions work against pages with BigPipe enabled

  # The 'behat_test' fixture uninstalls 'big_pipe' globally so that the
  # baseline test suite does not depend on BigPipe at all. Scenarios that
  # exercise the bypass install 'big_pipe' explicitly and tag themselves
  # '@bigpipe'; the FeatureContext '@bigpipe' AfterScenario hook
  # uninstalls the module so subsequent scenarios stay isolated.

  @test-drupal @api @bigpipe
  Scenario: Assert that BigPipe cookie is set when @bigpipe tag is present
    Given I install a "big_pipe" module
    When I am on the homepage
    Then the cookie "big_pipe_nojs" exists

  @test-drupal @api
  Scenario: Assert that BigPipe cookie is not set without the @bigpipe tag
    When I am on the homepage
    Then the cookie "big_pipe_nojs" does not exist

  @test-drupal @api @bigpipe
  Scenario: Assert that BigPipe cookie is preserved across user logins
    Given the following users:
      | name           | mail                       | roles         | status |
      | bigpipe_admin  | bigpipe_admin@example.com  | administrator | 1      |
    And I install a "big_pipe" module
    When I am on the homepage
    Then the cookie "big_pipe_nojs" exists
    When I am logged in as "bigpipe_admin"
    And I am on the homepage
    Then the cookie "big_pipe_nojs" exists
