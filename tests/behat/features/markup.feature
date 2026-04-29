Feature: MarkupContext
  As a developer
  I want to verify MarkupContext step definitions work correctly
  So that I can trust markup assertions and error messages

  @test-blackbox
  Scenario: Assert "Then I should see the button :button in the :region region" fails when button not found in region
    Given some behat configuration
    And scenario steps tagged with "@test-blackbox":
      """
      Given I am at "form_controls.html"
      Then I should see the button "Nonexistent" in the "static content" region
      """
    When I run behat
    Then it should fail with an error:
      """
      The button 'Nonexistent' was not found in the region 'static content'
      """

  @test-blackbox
  Scenario: Assert "Then I should not see the button :button in the :region region" fails when button is present
    Given some behat configuration
    And scenario steps tagged with "@test-blackbox":
      """
      Given I am at "form_controls.html"
      Then I should not see the button "Submit" in the "static content" region
      """
    When I run behat
    Then it should fail with an error:
      """
      The button 'Submit' was found in the region 'static content'
      """

  @test-blackbox
  Scenario: Assert "Then I should see the :tag element in the :region region" fails when element not found
    Given some behat configuration
    And scenario steps tagged with "@test-blackbox":
      """
      Given I am at "form_controls.html"
      Then I should see the "h99" element in the "static content" region
      """
    When I run behat
    Then it should fail with an error:
      """
      The element "h99" was not found in the "static content" region
      """

  @test-blackbox
  Scenario: Assert "Then I should not see the :tag element in the :region region" fails when element is present
    Given some behat configuration
    And scenario steps tagged with "@test-blackbox":
      """
      Given I am at "form_controls.html"
      Then I should not see the "h2" element in the "static content" region
      """
    When I run behat
    Then it should fail with an error:
      """
      The element "h2" was found in the "static content" region
      """

  @test-blackbox
  Scenario: Assert "Then I should see :text in the :tag element in the :region region" fails when text not found
    Given some behat configuration
    And scenario steps tagged with "@test-blackbox":
      """
      Given I am at "index.html"
      Then I should see "NONEXISTENT_TEXT" in the "h1" element in the "static left header" region
      """
    When I run behat
    Then it should fail with an error:
      """
      The text "NONEXISTENT_TEXT" was not found in the "h1" element in the "static left header" region
      """

  @test-blackbox
  Scenario: Assert "Then I should not see :text in the :tag element in the :region region" fails when text is present
    Given some behat configuration
    And scenario steps tagged with "@test-blackbox":
      """
      Given I am at "form_controls.html"
      Then I should not see "Content Heading" in the "h2" element in the "static content" region
      """
    When I run behat
    Then it should fail with an error:
      """
      The text "Content Heading" was found in the "h2" element in the "static content" region
      """

  @test-blackbox
  Scenario: Assert "Then I should see the :tag element with the :attribute attribute set to :value in the :region region" fails when attribute value does not match
    Given some behat configuration
    And scenario steps tagged with "@test-blackbox":
      """
      Given I am at "element_attributes.html"
      Then I should see the "div" element with the "class" attribute set to "nonexistent" in the "static left header" region
      """
    When I run behat
    Then it should fail with an error:
      """
      The "class" attribute does not equal "nonexistent" on the element "div" in the "static left header" region
      """

  @test-blackbox
  Scenario: Assert "Then I should see :text in the :tag element with the :attribute attribute set to :value in the :region region" fails when text not found
    Given some behat configuration
    And scenario steps tagged with "@test-blackbox":
      """
      Given I am at "element_attributes.html"
      Then I should see "NONEXISTENT" in the "h1" element with the "id" attribute set to "static-site-name" in the "static left header" region
      """
    When I run behat
    Then it should fail with an error:
      """
      The text "NONEXISTENT" was not found in the "h1" element in the "static left header" region
      """

  @test-blackbox
  Scenario: Assert "Then I should see :text in the :tag element with the :property CSS property set to :value in the :region region" fails when CSS value does not match
    Given some behat configuration
    And scenario steps tagged with "@test-blackbox":
      """
      Given I am at "element_attributes.html"
      Then I should see "footer" in the "p" element with the "color" CSS property set to "blue" in the "static footer" region
      """
    When I run behat
    Then it should fail with an error:
      """
      The "color" style property does not equal "blue" on the element "p" in the "static footer" region
      """
