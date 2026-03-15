Feature: RandomContext functionality
  As a developer
  I want to generate random values in test scenarios
  So that I can avoid data collisions between test runs

  # This will fail on the second scenario if random transforms are not functional.
  @test-drupal @api @random
  Scenario: Assert random variable transform passes for first user
    Given I am at "/user/register"
    And I fill in "Email address" with "<?user>@example.com"
    And I fill in "Username" with "<?user>"
    When I press "Create new account"
    Then an email has been sent to "<?user>@example.com" with the subject "Account details for <?user>"

  @test-drupal @api @random
  Scenario: Assert random variable transform passes for second user
    Given I am at "/user/register"
    And I fill in "Email address" with "<?user>@example.com"
    And I fill in "Username" with "<?user>"
    When I press "Create new account"
    Then an email has been sent to "<?user>@example.com" with the subject "Account details for <?user>"

  @test-drupal @api @random
  Scenario: Assert random variable transform in tables passes
    Given I am viewing a page:
      | title | <?random_page> |
    Then I should see the text "<?random_page>"

  @test-drupal @api
  Scenario: Assert random variable transform fails for undefined variable
    Given some behat configuration
    And scenario steps tagged with "@test-drupal @api @random":
      """
      Given I am on "/"
      Then I should see the text "<?undefined_var>"
      """
    When I run behat with drupal profile
    Then it should fail
