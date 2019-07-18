@blackbox
Feature: Test DrupalContext
  In order to prove the Drupal context using the blackbox driver is working properly
  As a developer
  I need to use the step definitions of this context

  Scenario: Test the ability to find a heading in a region
    Given I am on the homepage
    When I click "Download & Extend"
    Then I should see the heading "Download" in the "content" region

  Scenario: Clicking content in a region
    Given I am at "community.html"
    When I click "IRC" in the "content" region
    Then I should see "Page status" in the "right sidebar"
    And I should see the link "Drupal News" in the "footer" region

  Scenario: Viewing content in a region
    Given I am on the homepage
    Then I should see "Build something amazing." in the "left header"

  Scenario: Test ability to find text that should not appear in a region
    Given I am on the homepage
    Then I should not see the text "Proprietary software is cutting edge" in the "left header"

  Scenario: Submit a form in a region
    Given I am on the homepage
    When I fill in "Search…" with "Views" in the "navigation" region
    And I press "Search" in the "navigation" region
    Then I should see the text "Search again" in the "right sidebar" region

  Scenario: Check a link should not exist in a region
    Given I am on the homepage
    Then I should not see the link "This link should never exist in a default Drupal install" in the "right header"

  Scenario: Find a button
    Given I am on the homepage
    Then I should see the "Search" button

  Scenario: Find a button in a region
    Given I am on the homepage
    Then I should see the "Search" button in the "navigation"

  Scenario: Button not in region
    Given I am on the homepage
    Then I should not see the "Search" button in the "right header" region

  Scenario: Find an element in a region
    Given I am on the homepage
    Then I should see the "h1" element in the "left header"

  Scenario: Element not in region
    Given I am on the homepage
    Then I should not see the "h1" element in the "footer"

  Scenario: Text not in element in region
    Given I am on the homepage
    Then I should not see "DotNetNuke" in the "h1" element in the "left header"

  Scenario: Find an element with an attribute in a region
    Given I am on the homepage
    Then I should see the "h1" element with the "id" attribute set to "site-name" in the "left header" region

  Scenario: Find text in an element with an attribute in a region
    Given I am on the homepage
    Then I should see "Drupal" in the "h1" element with the "id" attribute set to "site-name" in the "left header" region

  Scenario: Find element with attribute set on a region
    Given I am at "assertRegionElementAttribute.html"
    Then I should see the "div" element with the "class" attribute set to "class1" in the "left header" region
    And I should see the "div" element with the "class" attribute set to "class2" in the "left header" region
    And I should see the "div" element with the "class" attribute set to "class3" in the "left header" region

  Scenario: Error messages
    Given I am on "user.html"
    When I press "Log in"
    Then I should see the error message "Password field is required"
    And I should not see the error message "Sorry, unrecognized username or password"
    And I should see the following error messages:
      | error messages                       |
      | Username or email field is required. |
      | Password field is required           |
    And I should not see the following error messages:
      | error messages                                                                |
      | Sorry, unrecognized username or password                                      |
      | Unable to send e-mail. Contact the site administrator if the problem persists |

  @scenariotag
  Scenario: Check tags on feature and scenario
    Then the "scenariotag" tag should be present
    And the "blackbox" tag should be present
    But the "nonexisting" tag should not be present
