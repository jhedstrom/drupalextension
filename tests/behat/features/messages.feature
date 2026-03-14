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

  @api @test-drupal
  Scenario: Multiple error messages on Drupal page
    Given I am at "/behat-test/messages"
    Then I should see the following error messages:
      | error messages         |
      | Test error message     |
      | Another error message  |

  @api @test-drupal
  Scenario: Not see error messages on Drupal page
    Given I am at "/user/login"
    Then I should not see the following error messages:
      | error messages    |
      | Access denied     |

  @api @test-drupal
  Scenario: Not see error message on Drupal page
    Given I am at "/user/login"
    Then I should not see the error message "Something went wrong"

  @api @test-drupal
  Scenario: Warning message on Drupal page
    Given I am at "/behat-test/messages"
    Then I should see the warning message "Test warning message"

  @api @test-drupal
  Scenario: Warning message containing on Drupal page
    Given I am at "/behat-test/messages"
    Then I should see the warning message containing "warning"

  @api @test-drupal
  Scenario: Multiple warning messages on Drupal page
    Given I am at "/behat-test/messages"
    Then I should see the following warning messages:
      | warning messages         |
      | Test warning message     |
      | Another warning message  |

  @api @test-drupal
  Scenario: Not see warning message on Drupal page
    Given I am at "/user/login"
    Then I should not see the warning message "No warning here"

  @api @test-drupal
  Scenario: Not see warning messages on Drupal page
    Given I am at "/user/login"
    Then I should not see the following warning messages:
      | warning messages   |
      | No warning here    |

  @api @test-drupal
  Scenario: Not see message on Drupal page
    Given I am at "/user/login"
    Then I should not see the message "Something happened"

  @test-blackbox
  Scenario: Multiple error messages assertion
    Given I am at "messages.html"
    Then I should see the following error messages:
      | error messages                        |
      | Username or email field is required.  |
      | Password field is required.           |

  @test-blackbox
  Scenario: Should not see error messages that are absent
    Given I am at "messages.html"
    Then I should not see the following error messages:
      | error messages    |
      | Access denied     |

  @test-blackbox
  Scenario: Should not see the error message on a page without errors
    Given I am at "index.html"
    Then I should not see the error message "Something went wrong"

  @test-blackbox
  Scenario: Warning message visible
    Given I am at "messages.html"
    Then I should see the warning message "This action cannot be undone"

  @test-blackbox
  Scenario: Warning message containing partial text
    Given I am at "messages.html"
    Then I should see the warning message containing "cannot be undone"

  @test-blackbox
  Scenario: Multiple warning messages assertion
    Given I am at "messages.html"
    Then I should see the following warning messages:
      | warning messages                      |
      | This action cannot be undone.         |
      | Your session is about to expire.      |

  @test-blackbox
  Scenario: Should not see warning message that is absent
    Given I am at "messages.html"
    Then I should not see the warning message "Everything is fine"

  @test-blackbox
  Scenario: Should not see warning messages that are absent
    Given I am at "messages.html"
    Then I should not see the following warning messages:
      | warning messages      |
      | This is not a warning |

  @test-blackbox
  Scenario: Should not see the message on a page without messages
    Given I am at "index.html"
    Then I should not see the message "Something happened"

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

  @test-blackbox
  Scenario: Fail when expected warning message is not present
    Given some behat configuration
    And scenario steps:
      """
      Given I am on "/user/login"
      Then I should see the warning message "This warning does not exist"
      """
    When I run "behat --no-colors"
    Then it should fail with an error:
      """
      does not contain any warning messages
      """

  @test-blackbox
  Scenario: Fail when success message is present but should not be
    Given some behat configuration
    And scenario steps:
      """
      Given I am logged in as a user with the "administrator" role
      And I am viewing an "article" with the title "Neg success test"
      And I click "Edit"
      And I press "Save"
      Then I should not see the success message "has been updated"
      """
    When I run "behat --no-colors"
    Then it should fail with an error:
      """
      contains the success message 'has been updated'
      """

  @test-blackbox
  Scenario: Fail when generic message is present but should not be
    Given some behat configuration
    And scenario steps:
      """
      Given I am on "/user/login"
      When I fill in "a fake user" for "Username"
      And I fill in "a fake password" for "Password"
      And I press "Log in"
      Then I should not see the message "Unrecognized username or password"
      """
    When I run "behat --no-colors"
    Then it should fail with an error:
      """
      contains the message 'Unrecognized username or password'
      """

  @test-blackbox
  Scenario: Fail when expected multiple error messages are not present
    Given some behat configuration
    And scenario steps:
      """
      Given I am on "/user/login"
      Then I should see the following error messages:
        | error messages          |
        | This error never exists |
      """
    When I run "behat --no-colors"
    Then it should fail with an error:
      """
      does not contain any error messages
      """

  @test-blackbox
  Scenario: Fail when error messages are present but should not be
    Given some behat configuration
    And scenario steps:
      """
      Given I am on "/user/login"
      When I fill in "a fake user" for "Username"
      And I fill in "a fake password" for "Password"
      And I press "Log in"
      Then I should not see the following error messages:
        | error messages                          |
        | Unrecognized username or password       |
      """
    When I run "behat --no-colors"
    Then it should fail with an error:
      """
      contains the error message 'Unrecognized username or password'
      """

  @test-blackbox
  Scenario: Fail when expected multiple success messages are not present
    Given some behat configuration
    And scenario steps:
      """
      Given I am on "/user/login"
      Then I should see the following success messages:
        | success messages         |
        | This success never exists |
      """
    When I run "behat --no-colors"
    Then it should fail with an error:
      """
      does not contain any success messages
      """

  @test-blackbox
  Scenario: Fail when success messages are present but should not be
    Given some behat configuration
    And scenario steps:
      """
      Given I am logged in as a user with the "administrator" role
      And I am viewing an "article" with the title "Neg multi success"
      And I click "Edit"
      And I press "Save"
      Then I should not see the following success messages:
        | success messages  |
        | has been updated  |
      """
    When I run "behat --no-colors"
    Then it should fail with an error:
      """
      contains the success message 'has been updated'
      """

  @test-blackbox
  Scenario: Fail when warning message is present but should not be
    Given some behat configuration
    And scenario steps:
      """
      Given I am on "/behat-test/messages"
      Then I should not see the warning message "Test warning message"
      """
    When I run "behat --no-colors"
    Then it should fail with an error:
      """
      contains the warning message 'Test warning message'
      """

  @test-blackbox
  Scenario: Fail when expected multiple warning messages are not present
    Given some behat configuration
    And scenario steps:
      """
      Given I am on "/user/login"
      Then I should see the following warning messages:
        | warning messages          |
        | This warning never exists |
      """
    When I run "behat --no-colors"
    Then it should fail with an error:
      """
      does not contain any warning messages
      """

  @test-blackbox
  Scenario: Fail when warning messages are present but should not be
    Given some behat configuration
    And scenario steps:
      """
      Given I am on "/behat-test/messages"
      Then I should not see the following warning messages:
        | warning messages        |
        | Test warning message    |
      """
    When I run "behat --no-colors"
    Then it should fail with an error:
      """
      contains the warning message 'Test warning message'
      """

  @test-blackbox
  Scenario: Fail when message table has wrong number of columns
    Given some behat configuration
    And scenario steps:
      """
      Given I am on "/behat-test/messages"
      Then I should see the following error messages:
        | error messages  | extra column |
        | Test error      | extra        |
      """
    When I run "behat --no-colors"
    Then it should fail with an exception:
      """
      should only contain 1 column
      """

  @test-blackbox
  Scenario: Fail when message table has wrong header
    Given some behat configuration
    And scenario steps:
      """
      Given I am on "/behat-test/messages"
      Then I should see the following error messages:
        | wrong header |
        | Test error   |
      """
    When I run "behat --no-colors"
    Then it should fail with an exception:
      """
      should have the header 'Error messages'
      """

  @test-blackbox
  Scenario: Fail when error message text not found but container exists
    Given some behat configuration
    And scenario steps:
      """
      Given I am on "/behat-test/messages"
      Then I should see the error message "This specific text does not exist"
      """
    When I run "behat --no-colors"
    Then it should fail with an error:
      """
      does not contain the error message 'This specific text does not exist'
      """
