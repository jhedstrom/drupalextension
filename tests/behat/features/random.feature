Feature: RandomContext functionality
  As a developer
  I want to generate random values in test scenarios
  So that I can avoid data collisions between test runs

  # This will fail on the second scenario if random transforms are not functional.
  @test-drupal @api @random
  Scenario: Create a first user
    Given I am at "/user/register"
    And I fill in "Email address" with "<?user>@example.com"
    And I fill in "Username" with "<?user>"
    When I press "Create new account"
    Then an email has been sent to "<?user>@example.com" with the subject "Account details for <?user>"

  @test-drupal @api @random
  Scenario: Create the second user
    Given I am at "/user/register"
    And I fill in "Email address" with "<?user>@example.com"
    And I fill in "Username" with "<?user>"
    When I press "Create new account"
    Then an email has been sent to "<?user>@example.com" with the subject "Account details for <?user>"

  @test-drupal @api @random
  Scenario: Test RandomContext functionality in tables
    Given I am viewing a page:
      | title | <?random_page> |
    Then I should see the text "<?random_page>"

  @test-drupal @api
  Scenario: Fail when using undefined random variable
    Given some behat configuration
    And a file named "features/stub.feature" with:
      """
      @test-drupal @api @random
      Feature: Stub
        Scenario: Undefined variable
          Given I am on "/"
          Then I should see the text "<?undefined_var>"
      """
    When I run behat with drupal profile
    Then it should fail
