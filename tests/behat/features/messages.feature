Feature: MessageContext
  As a developer
  I want to verify Drupal status messages in tests
  So that I can assert error, success, warning, and generic messages appear correctly

  @api @test-drupal
  Scenario: Error messages on failed login
    Given I am on "/user/login"
    When I fill in "a fake user" for "Username"
    And I fill in "a fake password" for "Password"
    And I press "Log in"
    Then I should see the error message "Unrecognized username or password"
    And I should not see the success message "logged in"
    And I should not see the warning message "logged in"

  @api @test-drupal
  Scenario: Success message after creating content
    Given I am logged in as a user with the "administrator" role
    And I am viewing an "article" with the title "Success message test"
    When I click "Edit"
    And I press "Save"
    Then I should see the success message "has been updated"
    And I should not see the error message "has been updated"

  @api @test-drupal
  Scenario: Success message containing partial text
    Given I am logged in as a user with the "administrator" role
    When I am viewing an "article" with the title "Partial message test"
    And I click "Edit"
    And I press "Save"
    Then I should see the success message containing "updated"

  @api @test-drupal
  Scenario: Multiple success messages assertion
    Given I am logged in as a user with the "administrator" role
    When I am viewing an "article" with the title "Multiple messages test"
    And I click "Edit"
    And I press "Save"
    Then I should see the following success messages:
      | success messages |
      | has been updated |

  @api @test-drupal
  Scenario: Should not see success messages that are absent
    Given I am logged in as a user with the "administrator" role
    When I am viewing an "article" with the title "No success test"
    Then I should not see the following success messages:
      | success messages        |
      | This should not be here |

  @api @test-drupal
  Scenario: Generic message assertion matches any message type
    Given I am on "/user/login"
    When I fill in "a fake user" for "Username"
    And I fill in "a fake password" for "Password"
    And I press "Log in"
    Then I should see the message "Unrecognized username or password"
    And I should not see the message "Everything is fine"

  @test-blackbox
  Scenario: Fail when expected error message is not present
    Given some behat configuration
    And scenario steps:
      """
      Given I am on "/user/login"
      Then I should see the error message "This error does not exist"
      """
    When I run "behat --no-colors"
    Then it should fail with an error:
      """
      does not contain any error messages
      """

  @test-blackbox
  Scenario: Fail when error message is present but should not be
    Given some behat configuration
    And scenario steps:
      """
      Given I am on "/user/login"
      When I fill in "a fake user" for "Username"
      And I fill in "a fake password" for "Password"
      And I press "Log in"
      Then I should not see the error message "Unrecognized username or password"
      """
    When I run "behat --no-colors"
    Then it should fail with an error:
      """
      contains the error message 'Unrecognized username or password'
      """

  @test-blackbox
  Scenario: Fail when expected success message is not present
    Given some behat configuration
    And scenario steps:
      """
      Given I am on "/user/login"
      Then I should see the success message "This success does not exist"
      """
    When I run "behat --no-colors"
    Then it should fail with an error:
      """
      does not contain any success messages
      """

  @test-blackbox
  Scenario: Fail when expected generic message is not present
    Given some behat configuration
    And scenario steps:
      """
      Given I am on "/user/login"
      Then I should see the message "Nonexistent message"
      """
    When I run "behat --no-colors"
    Then it should fail with an error:
      """
      does not contain any messages
      """
