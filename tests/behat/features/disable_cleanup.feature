Feature: Disable automatic cleanup
  As a developer debugging a failing scenario
  I want to inspect the entities, users, and roles created by the run
  So that I can see the state that produced the failure

  # Cleanup is controlled by 'BEHAT_DRUPALEXTENSION_DISABLE_CLEANUP'.
  # The signal we look for is whether 'cleanUsers()' wiped the user manager
  # singleton between scenarios in the inner Behat run:
  #   - Default (cleanup runs): scenario 2's 'I am logged in as :name' raises
  #     'InvalidArgumentException' because the previous user was cleared.
  #   - Env var set: scenario 2 logs in successfully because nothing was
  #     cleared.
  # See 'src/Drupal/DrupalExtension/Context/RawDrupalContext::shouldCleanup'.

  @test-drupal @api
  Scenario: Assert cleanup runs by default and the user is gone next scenario
    Given some behat configuration
    And a file named "features/stub.feature" with:
      """
      Feature: Inner stub

        @test-drupal @api
        Scenario: Create a user
          Given the following users:
            | name        | mail                |
            | TestPersist | persist@example.com |
          Then I am logged in as "TestPersist"

        @test-drupal @api
        Scenario: Verify user from previous scenario persists
          Given I am logged in as "TestPersist"
      """
    When I run behat with drupal profile
    Then it should fail with a "InvalidArgumentException" exception:
      """
      No user with TestPersist name is registered with the driver.
      """

  @test-drupal @api
  Scenario: Assert env var "1" disables cleanup so the user persists
    Given some behat configuration
    And the "BEHAT_DRUPALEXTENSION_DISABLE_CLEANUP" environment variable is set to "1"
    And a file named "features/stub.feature" with:
      """
      Feature: Inner stub

        @test-drupal @api
        Scenario: Create a user
          Given the following users:
            | name              | mail                       |
            | TestPersistEnvOn  | persist-env-on@example.com |
          Then I am logged in as "TestPersistEnvOn"

        @test-drupal @api
        Scenario: Verify user from previous scenario persists
          Given I am logged in as "TestPersistEnvOn"
      """
    When I run behat with drupal profile
    Then it should pass with:
      """
      2 scenarios (2 passed)
      """
    # The inner run intentionally skipped its own cleanup, so the user is
    # still in Drupal. Remove it here so re-running the suite locally does
    # not collide on the next user create.
    And I run drush "user:cancel" "TestPersistEnvOn -y --delete-content"
