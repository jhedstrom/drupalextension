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
    Then there should be a total of 1 email sent to "<?user>@example.com" with the subject "Account details for <?user>"

  @test-drupal @api @random
  Scenario: Assert random variable transform passes for second user
    Given I am at "/user/register"
    And I fill in "Email address" with "<?user>@example.com"
    And I fill in "Username" with "<?user>"
    When I press "Create new account"
    Then there should be a total of 1 email sent to "<?user>@example.com" with the subject "Account details for <?user>"

  @test-drupal @api @random
  Scenario: Assert random variable transform in tables passes
    Given I am viewing a page with the following fields:
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

  # The two scenarios below prove that 'RandomContext' can run without the
  # Drupal API driver. They register against the 'default' (blackbox) profile
  # and rely on the placeholder transform happening before the assertion.

  @test-blackbox @random
  Scenario: Assert random variable transform passes in blackbox profile
    Given some behat configuration
    And scenario steps tagged with "@test-blackbox @random":
      """
      Given I am at "index.html"
      Then I should not see the text "<?token>"
      """
    When I run behat
    Then it should pass with:
      """
      1 scenario (1 passed)
      """

  @test-blackbox @random
  Scenario: Assert random variable transform fails when token text expected
    Given some behat configuration
    And scenario steps tagged with "@test-blackbox @random":
      """
      Given I am at "index.html"
      Then I should see the text "<?token>"
      """
    When I run behat
    Then it should fail
