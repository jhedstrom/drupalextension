Feature: MessageContext

  As a developer
  I want to verify Drupal status messages in tests
  So that I can assert error, success, warning, and generic messages appear correctly

  @test-drupal @api
  Scenario: Assert "Then I should see the error message :message" passes
    Given I am on "/user/login"
    When I fill in "a fake user" for "Username"
    And I fill in "a fake password" for "Password"
    And I press "Log in"
    Then I should see the error message "Unrecognized username or password"

  @test-drupal @api
  Scenario: Assert "Then I should see the error message :message" fails when error not present
    Given some behat configuration
    And scenario steps tagged with "@test-drupal @api":
      """
      Given I am on "/user/login"
      Then I should see the error message "This error does not exist"
      """
    When I run behat with drupal profile
    Then it should fail with an error:
      """
      does not contain any error messages
      """

  @test-drupal @api
  Scenario: Assert "Then I should not see the error message :message" fails when error is present
    Given some behat configuration
    And scenario steps tagged with "@test-drupal @api":
      """
      Given I am on "/user/login"
      When I fill in "a fake user" for "Username"
      And I fill in "a fake password" for "Password"
      And I press "Log in"
      Then I should not see the error message "Unrecognized username or password"
      """
    When I run behat with drupal profile
    Then it should fail with an error:
      """
      contains the error message 'Unrecognized username or password'
      """

  @test-drupal @api
  Scenario: Assert "Then I should see the success message :message" passes
    Given I am logged in as a user with the "administrator" role
    And I am viewing an "article" with the title "Success message test"
    When I click "Edit"
    And I press "Save"
    Then I should see the success message "has been updated"

  @test-drupal @api
  Scenario: Assert "Then I should see the success message containing :message" passes
    Given I am logged in as a user with the "administrator" role
    When I am viewing an "article" with the title "Partial message test"
    And I click "Edit"
    And I press "Save"
    Then I should see the success message containing "updated"

  @test-drupal @api
  Scenario: Assert "Then I should see the success message :message" fails when success not present
    Given some behat configuration
    And scenario steps tagged with "@test-drupal @api":
      """
      Given I am on "/user/login"
      Then I should see the success message "This success does not exist"
      """
    When I run behat with drupal profile
    Then it should fail with an error:
      """
      does not contain any success messages
      """

  @test-drupal @api
  Scenario: Assert "Then I should not see the success message :message" fails when success is present
    Given some behat configuration
    And scenario steps tagged with "@test-drupal @api":
      """
      Given I am logged in as a user with the "administrator" role
      And I am viewing an "article" with the title "Should not see success"
      When I click "Edit"
      And I press "Save"
      Then I should not see the success message "has been updated"
      """
    When I run behat with drupal profile
    Then it should fail with an error:
      """
      contains the success message 'has been updated'
      """

  @test-drupal @api
  Scenario: Assert "Then I should see the warning message :message" fails when warning not present
    Given some behat configuration
    And scenario steps tagged with "@test-drupal @api":
      """
      Given I am on "/user/login"
      Then I should see the warning message "This warning does not exist"
      """
    When I run behat with drupal profile
    Then it should fail with an error:
      """
      does not contain any warning messages
      """

  @test-drupal @api
  Scenario: Assert "Then I should see the message :message" passes
    Given I am on "/user/login"
    When I fill in "a fake user" for "Username"
    And I fill in "a fake password" for "Password"
    And I press "Log in"
    Then I should see the message "Unrecognized username or password"

  @test-drupal @api
  Scenario: Assert "Then I should see the message :message" fails when message not present
    Given some behat configuration
    And scenario steps tagged with "@test-drupal @api":
      """
      Given I am on "/user/login"
      Then I should see the message "Nonexistent message"
      """
    When I run behat with drupal profile
    Then it should fail with an error:
      """
      does not contain any messages
      """

  @test-drupal @api
  Scenario: Assert "Then I should not see the message :message" fails when message is present
    Given some behat configuration
    And scenario steps tagged with "@test-drupal @api":
      """
      Given I am on "/user/login"
      When I fill in "a fake user" for "Username"
      And I fill in "a fake password" for "Password"
      And I press "Log in"
      Then I should not see the message "Unrecognized username or password"
      """
    When I run behat with drupal profile
    Then it should fail with an error:
      """
      contains the message 'Unrecognized username or password'
      """

  @test-drupal @api
  Scenario: Assert "Then I should see the following success messages:" passes
    Given I am logged in as a user with the "administrator" role
    When I am viewing an "article" with the title "Multiple messages test"
    And I click "Edit"
    And I press "Save"
    Then I should see the following success messages:
      | success messages |
      | has been updated |

  @test-drupal @api
  Scenario: Assert "Then I should not see the following success messages:" passes
    Given I am logged in as a user with the "administrator" role
    When I am viewing an "article" with the title "No success test"
    Then I should not see the following success messages:
      | success messages        |
      | This should not be here |

  @test-drupal @api
  Scenario: Assert "Then I should see the following error messages:" passes
    Given I am on "/user/login"
    When I fill in "a fake user" for "Username"
    And I fill in "a fake password" for "Password"
    And I press "Log in"
    Then I should see the following error messages:
      | error messages                    |
      | Unrecognized username or password |

  @test-drupal @api
  Scenario: Assert "Then I should see the following error messages:" fails when error not present
    Given some behat configuration
    And scenario steps tagged with "@test-drupal @api":
      """
      Given I am on "/user/login"
      Then I should see the following error messages:
        | error messages            |
        | This error does not exist |
      """
    When I run behat with drupal profile
    Then it should fail with an error:
      """
      does not contain any error messages
      """

  @test-drupal @api
  Scenario: Assert "Then I should not see the following error messages:" fails when error is present
    Given some behat configuration
    And scenario steps tagged with "@test-drupal @api":
      """
      Given I am on "/user/login"
      When I fill in "a fake user" for "Username"
      And I fill in "a fake password" for "Password"
      And I press "Log in"
      Then I should not see the following error messages:
        | error messages                   |
        | Unrecognized username or password |
      """
    When I run behat with drupal profile
    Then it should fail with an error:
      """
      contains the error message 'Unrecognized username or password'
      """

  @test-drupal @api
  Scenario: Assert "Then I should see the following warning messages:" fails when warning not present
    Given some behat configuration
    And scenario steps tagged with "@test-drupal @api":
      """
      Given I am on "/user/login"
      Then I should see the following warning messages:
        | warning messages              |
        | This warning does not exist   |
      """
    When I run behat with drupal profile
    Then it should fail with an error:
      """
      does not contain any warning messages
      """

  @test-drupal @api
  Scenario: Assert "Then I should not see the following warning messages:" passes when not present
    Given I am on "/user/login"
    Then I should not see the following warning messages:
      | warning messages        |
      | This should not be here |

  @test-drupal @api
  Scenario: Assert "Then I should see the following error messages:" fails for missing header
    Given some behat configuration
    And scenario steps tagged with "@test-drupal @api":
      """
      Given I am on "/user/login"
      When I fill in "a fake user" for "Username"
      And I fill in "a fake password" for "Password"
      And I press "Log in"
      Then I should see the following error messages:
        | Unrecognized username or password |
      """
    When I run behat with drupal profile
    Then it should fail with an exception:
      """
      should have the header 'Error messages', but found 'Unrecognized username or password'
      """

  @test-drupal @api
  Scenario: Assert "Then I should see the following success messages:" fails for multi-column table
    Given some behat configuration
    And scenario steps tagged with "@test-drupal @api":
      """
      Given I am logged in as a user with the "administrator" role
      And I am viewing an "article" with the title "Multi column test"
      When I click "Edit"
      And I press "Save"
      Then I should see the following success messages:
        | success messages | extra column |
        | has been updated | extra value  |
      """
    When I run behat with drupal profile
    Then it should fail with an exception:
      """
      should only contain 1 column
      """

  @test-drupal @api
  Scenario: Assert "Then I should not see the error message :message" passes when not present
    Given I am on "/user/login"
    Then I should not see the error message "logged in"

  @test-drupal @api
  Scenario: Assert "Then I should not see the warning message :message" passes when not present
    Given I am on "/user/login"
    Then I should not see the warning message "logged in"
