Feature: Test DrupalContext
  In order to prove the Drupal context using the blackbox driver is working properly
  As a developer
  I need to use the step definitions of this context

  Scenario: Test the ability to find a heading in a region
    Given I am on the homepage
    When I click "Download & Extend"
    Then I should see the heading "Core" in the "content" region

  Scenario: Clicking content in a region
    Given I am at "download"
    When I click "About Distributions" in the "content" region
    Then I should see "Page status" in the "right sidebar"
    And I should see the link "Drupal News" in the "footer" region

  Scenario: Viewing content in a region
    Given I am on the homepage
    Then I should see "Come for the software, stay for the community" in the "left header"

  Scenario: Test ability to find text that should not appear in a region
    Given I am on the homepage
    Then I should not see the text "Proprietary software is cutting edge" in the "left header"

  Scenario: Press a button in a region
    Given I am on the homepage
    When I press "Search" in the "right header" region
    Then I should see the text "Filter by content type" in the "content" region

  Scenario: Error messages
   Given I am on "/user"
   When I press "Log in"
   Then I should see the error message "Password field is required"
   And I should not see the error message "Sorry, unrecognized username or password"
   And I should see the following <error messages>
   | error messages             |
   | Username field is required |
   | Password field is required |
   And I should not see the following <error messages>
   | error messages                                                                |
   | Sorry, unrecognized username or password                                      |
   | Unable to send e-mail. Contact the site administrator if the problem persists |

 Scenario: Messages
   Given I am on "/user/register"
   When I press "Create new account"
   Then I should see the message "Username field is required"
   But I should not see the message "Registration successful. You are now logged in"
