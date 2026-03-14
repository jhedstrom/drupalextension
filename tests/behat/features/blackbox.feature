@test-blackbox
Feature: Test DrupalContext
  As a developer
  I want to interact with page regions and elements using the blackbox driver
  So that I can test UI behaviour without direct Drupal API access

  Scenario: Test the ability to find a heading in a region
    Given I am on the homepage
    When I click "Page Two"
    Then I should see the heading "Download" in the "static content" region

  Scenario: Clicking content in a region
    Given I am at "page_one.html"
    When I click "Page Three" in the "static content" region
    Then I should see "Page status" in the "static sidebar"
    And I should see the link "Home" in the "static footer" region

  Scenario: Viewing content in a region
    Given I am on the homepage
    Then I should see "Welcome to the test site." in the "static left header"

  Scenario: Test ability to find text that should not appear in a region
    Given I am on the homepage
    Then I should not see the text "Proprietary software is cutting edge" in the "static left header"

  Scenario: Submit a form in a region
    Given I am on the homepage
    When I fill in "Search…" with "Views" in the "static navigation" region
    And I check "extra" in the "static navigation" region
    And I press "Search" in the "static navigation" region
    Then I should see the text "Home" in the "static sidebar" region

  Scenario: Check a link should not exist in a region
    Given I am on the homepage
    Then I should not see the link "This link should never exist in a default Drupal install" in the "static right header"

  Scenario: Find a button
    Given I am on the homepage
    Then I should see the "Search" button

  Scenario: Find a button in a region
    Given I am on the homepage
    Then I should see the "Search" button in the "static navigation"

  Scenario: Button not in region
    Given I am on the homepage
    Then I should not see the "Search" button in the "static right header" region

  Scenario: Find an element in a region
    Given I am on the homepage
    Then I should see the "h1" element in the "static left header"

  Scenario: Element not in region
    Given I am on the homepage
    Then I should not see the "h1" element in the "static footer"

  Scenario: Text in element in region
    Given I am on the homepage
    Then I should see "Test Static Site" in the "h1" element in the "static left header"

  Scenario: Text not in element in region
    Given I am on the homepage
    Then I should not see "DotNetNuke" in the "h1" element in the "static left header"

  Scenario: Find an element with an attribute in a region
    Given I am on the homepage
    Then I should see the "h1" element with the "id" attribute set to "static-site-name" in the "static left header" region

  Scenario: Find text in an element with an attribute in a region
    Given I am on the homepage
    Then I should see "Test Static Site" in the "h1" element with the "id" attribute set to "static-site-name" in the "static left header" region

  Scenario: Find element with attribute set on a region
    Given I am at "element_attributes.html"
    Then I should see the "div" element with the "class" attribute set to "class1" in the "static left header" region
    And I should see the "div" element with the "class" attribute set to "class2" in the "static left header" region
    And I should see the "div" element with the "class" attribute set to "class3" in the "static left header" region

  Scenario: Interact with <details> elements
    Given I am at "page_three.html"
    When I click details labelled "Click to read more"
    Then I should see the text "This is additional content that is hidden by default and can be expanded."

  Scenario: Error messages
    Given I am on "form_page.html"
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
    # Tags inherited from the feature should be present on the scenario
    And the "test-blackbox" tag should be present
    But the "nonexisting" tag should not be present

  Scenario: Regression test. See:
  - https://github.com/minkphp/MinkBrowserKitDriver/issues/153
  - https://www.drupal.org/project/drupal/issues/3175718
  - https://www.drupal.org/project/drupal/issues/3295472
  - https://github.com/jhedstrom/drupalextension/pull/612
    Given I am on the homepage
    Then I should not see "This text shouldn't be visible"
