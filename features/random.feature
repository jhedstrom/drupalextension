@api @d8 @random
Feature: RandomContext functionality
  In order to prove the RandomContext is functional at transforming variables
  As a developer
  I need to use random variables in scenarios

  # This will fail on the second scenario if random transforms are not functional.
  Scenario: Create a first user
    Given I am at "/user/register"
    And I fill in "Email address" with "<?user>@example.com"
    And I fill in "Username" with "<?user>"
    When I press "Create new account"
    Then an email has been sent to "<?user>@example.com" with the subject "Account details for <?user>"

  Scenario: Create the second user
    Given I am at "/user/register"
    And I fill in "Email address" with "<?user>@example.com"
    And I fill in "Username" with "<?user>"
    When I press "Create new account"
    Then an email has been sent to "<?user>@example.com" with the subject "Account details for <?user>"
