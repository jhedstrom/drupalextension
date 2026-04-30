Feature: BigPipe NOJS bypass

  As a developer
  I want to opt scenarios into the BigPipe NOJS bypass via a tag
  So that authenticated-user assertions work against pages with BigPipe enabled

  # 'big_pipe' is installed by default via the 'behat_test' fixture so each
  # scenario can exercise BigPipe streaming. Tag a scenario or feature with
  # '@bigpipe' to make 'DrupalContext' set the 'big_pipe_nojs' cookie on
  # every step; without that tag the hooks are no-ops.

  @test-drupal @api @bigpipe
  Scenario: Assert that BigPipe cookie is set when @bigpipe tag is present
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
    When I am on the homepage
    Then the cookie "big_pipe_nojs" exists
    When I am logged in as "bigpipe_admin"
    And I am on the homepage
    Then the cookie "big_pipe_nojs" exists
