Feature: MarkupContext
  As a developer
  I want to verify MarkupContext step definitions work correctly
  So that I can trust markup assertions and error messages

  @test-blackbox
  Scenario: CSS property assertion in English
    Given I am at "element_attributes.html"
    Then I should see "footer" in the "p" element with the "color" CSS property set to "red" in the "static footer" region

  @api @test-drupal
  Scenario: See button in region on Drupal page
    Given I am at "/user/login"
    Then I should see the button "Log in" in the "main content" region

  @api @test-drupal
  Scenario: Not see button in region on Drupal page
    Given I am at "/user/login"
    Then I should not see the button "Nonexistent" in the "main content" region

  @api @test-drupal
  Scenario: See element in region on Drupal page
    Given I am at "/user/login"
    Then I should see the "h1" element in the "main content" region

  @api @test-drupal
  Scenario: Not see element in region on Drupal page
    Given I am at "/user/login"
    Then I should not see the "h99" element in the "main content" region

  @api @test-drupal
  Scenario: See text in element in region on Drupal page
    Given I am at "/user/login"
    Then I should see "Log in" in the "h1" element in the "main content" region

  @api @test-drupal
  Scenario: Not see text in element in region on Drupal page
    Given I am at "/user/login"
    Then I should not see "NONEXISTENT" in the "h1" element in the "main content" region

  @api @test-drupal
  Scenario: See element with attribute in region on Drupal page
    Given I am at "/user/login"
    Then I should see the "input" element with the "name" attribute set to "name" in the "main content" region

  @api @test-drupal
  Scenario: See text in element with attribute in region on Drupal page
    Given I am at "/user/login"
    Then I should see "Username" in the "label" element with the "for" attribute set to "edit-name" in the "main content" region

  @api @test-drupal
  Scenario: CSS property assertion on Drupal page
    Given I am at "/behat-test/messages"
    Then I should see "This page displays test messages." in the "p" element with the "color" CSS property set to "green" in the "main content" region

  @test-blackbox
  Scenario: Fail when button missing in region
    Given some behat configuration
    And scenario steps:
      """
      Given I am on "/user/login"
      Then I should see the button "Nonexistent" in the "main content" region
      """
    When I run "behat --no-colors"
    Then it should fail with an error:
      """
      The button 'Nonexistent' was not found in the region 'main content'
      """

  @test-blackbox
  Scenario: Fail when button is present in region but should not be
    Given some behat configuration
    And scenario steps:
      """
      Given I am on "/user/login"
      Then I should not see the button "Log in" in the "main content" region
      """
    When I run "behat --no-colors"
    Then it should fail with an error:
      """
      The button 'Log in' was found in the region 'main content'
      """

  @test-blackbox
  Scenario: Fail when element is present in region but should not be
    Given some behat configuration
    And scenario steps:
      """
      Given I am on "/user/login"
      Then I should not see the "h1" element in the "main content" region
      """
    When I run "behat --no-colors"
    Then it should fail with an error:
      """
      The element "h1" was found in the "main content" region
      """

  @test-blackbox
  Scenario: Fail when text is present in element in region but should not be
    Given some behat configuration
    And scenario steps:
      """
      Given I am on "/user/login"
      Then I should not see "Log in" in the "h1" element in the "main content" region
      """
    When I run "behat --no-colors"
    Then it should fail with an error:
      """
      The text "Log in" was found in the "h1" element in the "main content" region
      """

  @test-blackbox
  Scenario: Fail when attribute value is wrong on element in region
    Given some behat configuration
    And scenario steps:
      """
      Given I am on "/user/login"
      Then I should see the "input" element with the "name" attribute set to "wrong_name" in the "main content" region
      """
    When I run "behat --no-colors"
    Then it should fail with an error:
      """
      The "name" attribute does not equal "wrong_name"
      """

  @test-blackbox
  Scenario: Fail when text with attribute is wrong in element in region
    Given some behat configuration
    And scenario steps:
      """
      Given I am on "/user/login"
      Then I should see "NONEXISTENT" in the "h1" element with the "id" attribute set to "block-olivero-page-title" in the "main content" region
      """
    When I run "behat --no-colors"
    Then it should fail with an error:
      """
      The text "NONEXISTENT" was not found in the "h1" element in the "main content" region
      """

  @test-blackbox
  Scenario: Fail when CSS property value is wrong on element in region
    Given some behat configuration
    And scenario steps:
      """
      Given I am on "/behat-test/messages"
      Then I should see "Test error message" in the "div" element with the "color" CSS property set to "blue" in the "main content" region
      """
    When I run "behat --no-colors"
    Then it should fail

  @test-blackbox
  Scenario: Fail when element not found in region
    Given some behat configuration
    And scenario steps:
      """
      Given I am on "/"
      Then I should see the "h99" element in the "main content" region
      """
    When I run "behat --no-colors"
    Then it should fail with an error:
      """
      The element "h99" was not found in the "main content" region
      """

  @test-blackbox
  Scenario: Fail when text not found in element in region
    Given some behat configuration
    And scenario steps:
      """
      Given I am on "/user/login"
      Then I should see "NONEXISTENT_TEXT" in the "h1" element in the "main content" region
      """
    When I run "behat --no-colors"
    Then it should fail with an error:
      """
      The text "NONEXISTENT_TEXT" was not found in the "h1" element in the "main content" region
      """
