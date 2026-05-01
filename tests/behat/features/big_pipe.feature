Feature: BigPipe NOJS bypass

  As a developer
  I want to opt scenarios into the BigPipe NOJS bypass via a tag
  So that authenticated-user assertions work against pages with BigPipe enabled

  # The 'behat_test' fixture uninstalls 'big_pipe' globally so that the
  # baseline test suite does not depend on BigPipe at all. Scenarios that
  # exercise the bypass install 'big_pipe' explicitly and tag themselves
  # '@bigpipe'; the FeatureContext '@bigpipe' AfterScenario hook
  # uninstalls the module so subsequent scenarios stay isolated.

  # ----- Cookie state -----

  @test-drupal @api @bigpipe
  Scenario: BigPipe cookie is set when '@bigpipe' is present on a non-JS driver
    Given I install a "big_pipe" module
    When I am on the homepage
    Then the cookie "big_pipe_nojs" exists

  @test-drupal @api
  Scenario: BigPipe cookie is not set without the '@bigpipe' tag
    When I am on the homepage
    Then the cookie "big_pipe_nojs" does not exist

  @test-drupal @api @javascript @bigpipe
  Scenario: BigPipe cookie is not set on a JavaScript driver even with '@bigpipe'
    Given I install a "big_pipe" module
    When I am on the homepage
    Then the cookie "big_pipe_nojs" does not exist

  # ----- End-to-end content rendering -----
  # Without '@bigpipe', the admin toolbar's account menu - "View profile",
  # "Edit profile", "Log out" - is BigPipe-streamed via the
  # 'user.toolbar_link_builder' lazy builder. The placeholder is replaced
  # by a '<script type="application/vnd.drupal-ajax">' block that
  # BrowserKit / Goutte cannot execute, so the toolbar links never become
  # part of the DOM and 'findLink()' cannot locate them. With '@bigpipe',
  # Drupal renders the toolbar server-side and the links appear as real
  # '<a>' elements in the initial response.

  @test-drupal @api @bigpipe
  Scenario: Authenticated toolbar links are hidden in BigPipe placeholders without '@bigpipe'
    Given some behat configuration
    And scenario steps tagged with "@test-drupal @api":
      """
      Given the following users:
        | name           | mail                       | roles         | status |
        | bigpipe_admin  | bigpipe_admin@example.com  | administrator | 1      |
      And I install a "big_pipe" module
      When I am logged in as "bigpipe_admin"
      And I am on "/admin"
      Then I should see the link "Edit profile"
      """
    When I run behat with drupal profile
    Then it should fail with an error:
      """
      Link with id|title|alt|text "Edit profile" not found
      """

  @test-drupal @api @bigpipe
  Scenario: Authenticated toolbar links are rendered server-side with '@bigpipe'
    Given the following users:
      | name           | mail                       | roles         | status |
      | bigpipe_admin  | bigpipe_admin@example.com  | administrator | 1      |
    And I install a "big_pipe" module
    When I am logged in as "bigpipe_admin"
    And I am on "/admin"
    Then I should see the link "Edit profile"

  # ----- Cookie persistence across login redirects -----

  @test-drupal @api @bigpipe
  Scenario: BigPipe cookie is preserved across user logins
    Given the following users:
      | name           | mail                       | roles         | status |
      | bigpipe_admin  | bigpipe_admin@example.com  | administrator | 1      |
    And I install a "big_pipe" module
    When I am on the homepage
    Then the cookie "big_pipe_nojs" exists
    When I am logged in as "bigpipe_admin"
    And I am on the homepage
    Then the cookie "big_pipe_nojs" exists
