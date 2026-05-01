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

  # The scenarios below cover the modern '[?<name>:<type>[,<args>]]' form.

  @test-drupal @api @random
  Scenario: Assert "[?user]" transform passes for user creation
    Given I am at "/user/register"
    And I fill in "Email address" with "[?user]@example.com"
    And I fill in "Username" with "[?user]"
    When I press "Create new account"
    Then there should be a total of 1 email sent to "[?user]@example.com" with the subject "Account details for [?user]"

  @test-drupal @api @random
  Scenario: Assert "[?random_page]" transform passes in tables
    Given I am viewing a page with the following fields:
      | title | [?random_page] |
    Then I should see the text "[?random_page]"

  @test-drupal @api @random
  Scenario: Assert legacy "<?title>" and modern "[?title]" share the same value
    Given I am viewing a page with the following fields:
      | title | <?title> |
    Then I should see the text "[?title]"

  @test-drupal @api @random
  Scenario: Assert typed "[?slug:machine_name,8]" produces machine-name shape
    Given I am viewing a page with the following fields:
      | title | prefix-[?slug:machine_name,8]-suffix |
    Then I should see the text "[?slug:machine_name,8]"

  @test-blackbox @random
  Scenario: Assert "[?token]" transform passes in blackbox profile
    Given some behat configuration
    And scenario steps tagged with "@test-blackbox @random":
      """
      Given I am at "index.html"
      Then I should not see the text "[?token]"
      """
    When I run behat
    Then it should pass with:
      """
      1 scenario (1 passed)
      """

  @test-blackbox @random
  Scenario: Assert "[?token]" transform fails when token text expected
    Given some behat configuration
    And scenario steps tagged with "@test-blackbox @random":
      """
      Given I am at "index.html"
      Then I should see the text "[?token]"
      """
    When I run behat
    Then it should fail

  @test-blackbox @random
  Scenario: Assert "[?slug:machine_name,8]" produces machine-name shape under blackbox
    Given some behat configuration
    And scenario steps tagged with "@test-blackbox @random":
      """
      Given I am at "index.html"
      Then I should not see the text "[?slug:machine_name,8]"
      """
    When I run behat
    Then it should pass with:
      """
      1 scenario (1 passed)
      """

  # Legacy '<?token>' deprecation assertions.

  @test-blackbox @random
  Scenario: Assert legacy "<?token>" triggers a deprecation
    Given some behat configuration
    And scenario steps tagged with "@test-blackbox @random":
      """
      Given I am at "index.html"
      Then I should not see the text "<?token>"
      """
    When I run behat
    Then the output should contain:
      """
      [Deprecation] The "<?token>" token syntax is deprecated. Use "[?token]" instead.
      """

  @test-blackbox @random
  Scenario: Assert modern "[?token]" does not trigger a deprecation
    Given some behat configuration
    And scenario steps tagged with "@test-blackbox @random":
      """
      Given I am at "index.html"
      Then I should not see the text "[?token]"
      """
    When I run behat
    Then the output should not contain:
      """
      [Deprecation]
      """
