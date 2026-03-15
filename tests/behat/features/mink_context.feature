Feature: MinkContext coverage gaps
  As a developer
  I want comprehensive tests for MinkContext step definitions
  So that I can verify navigation, form interactions, and assertions work correctly

  @test-blackbox
  Scenario: Assert "Then I should not see the heading :heading" passes when heading is absent
    Given I am at "form_controls.html"
    Then I should not see the heading "Nonexistent Heading"

  @test-blackbox
  Scenario: Assert "Then I should see the heading :heading" fails when heading not found
    Given some behat configuration
    And scenario steps tagged with "@test-blackbox":
      """
      Given I am at "form_controls.html"
      Then I should see the heading "Does Not Exist"
      """
    When I run behat
    Then it should fail with an error:
      """
      The text 'Does Not Exist' was not found in any heading
      """

  @test-blackbox
  Scenario: Assert "Then I should not see the heading :heading" fails when heading is present
    Given some behat configuration
    And scenario steps tagged with "@test-blackbox":
      """
      Given I am at "form_controls.html"
      Then I should not see the heading "Content Heading"
      """
    When I run behat
    Then it should fail with an error:
      """
      The text 'Content Heading' was found in a heading
      """

  @test-blackbox
  Scenario: Assert "Then I should not see the button :button" passes when button is absent
    Given I am at "form_controls.html"
    Then I should not see the button "Nonexistent Button"

  @test-blackbox
  Scenario: Assert "Then I should see the button :button" fails when button not found
    Given some behat configuration
    And scenario steps tagged with "@test-blackbox":
      """
      Given I am at "form_controls.html"
      Then I should see the button "Missing Button"
      """
    When I run behat
    Then it should fail with an error:
      """
      The button 'Missing Button' was not found on the page
      """

  @test-blackbox
  Scenario: Assert "Then I should not see the button :button" fails when button is present
    Given some behat configuration
    And scenario steps tagged with "@test-blackbox":
      """
      Given I am at "form_controls.html"
      Then I should not see the button "Submit"
      """
    When I run behat
    Then it should fail with an error:
      """
      The button 'Submit' was found on the page
      """

  @test-blackbox
  Scenario: Assert "Then I should see the link :link" fails when link not found
    Given some behat configuration
    And scenario steps tagged with "@test-blackbox":
      """
      Given I am at "form_controls.html"
      Then I should see the link "Nonexistent link"
      """
    When I run behat
    Then it should fail with an error:
      """
      No link to 'Nonexistent link'
      """

  @test-blackbox
  Scenario: Assert "Then I should not see the link :link" fails when link is present
    Given some behat configuration
    And scenario steps tagged with "@test-blackbox":
      """
      Given I am at "form_controls.html"
      Then I should not see the link "Visible link"
      """
    When I run behat
    Then it should fail with an error:
      """
      The link 'Visible link' was present on the page
      """

  @test-blackbox
  Scenario: Assert "Then I should not visibly see the link :link" passes for hidden link
    Given I am at "form_controls.html"
    Then I should not visibly see the link "Hidden link"

  @test-blackbox
  Scenario: Assert "Then I should not visibly see the link :link" fails when link not loaded
    Given some behat configuration
    And scenario steps tagged with "@test-blackbox":
      """
      Given I am at "form_controls.html"
      Then I should not visibly see the link "Totally missing link"
      """
    When I run behat
    Then it should fail with an error:
      """
      The link 'Totally missing link' was not loaded on the page
      """

  @test-blackbox
  Scenario: Assert "Given I check the box :checkbox" passes
    Given I am at "form_controls.html"
    When I check the box "I agree to terms"

  @test-blackbox
  Scenario: Assert "Given I uncheck the box :checkbox" passes
    Given I am at "form_controls.html"
    When I uncheck the box "Subscribe to newsletter"

  @test-blackbox
  Scenario: Assert "Given I check :locator in the :region" and "Given I uncheck :locator in the :region" passes
    Given I am at "form_controls.html"
    And I check "Footer checkbox" in the "static footer" region
    Then I uncheck "Footer checkbox" in the "static footer" region

  @test-blackbox
  Scenario: Assert "When I select the radio button :label with the id :id" passes
    Given I am at "form_controls.html"
    When I select the radio button "Blue" with the id "color-blue"

  @test-blackbox
  Scenario: Assert "When I select the radio button :label" passes
    Given I am at "form_controls.html"
    When I select the radio button "Green"

  @test-blackbox
  Scenario: Assert "When I select the radio button :label" fails when radio not found
    Given some behat configuration
    And scenario steps tagged with "@test-blackbox":
      """
      Given I am at "form_controls.html"
      When I select the radio button "Nonexistent Option"
      """
    When I run behat
    Then it should fail with an error:
      """
      The radio button with "Nonexistent Option" was not found on the page
      """

  @test-blackbox
  Scenario: Assert "Then I should not get a :code HTTP response" passes
    Given I am at "form_controls.html"
    Then I should not get a "404" HTTP response

  @test-blackbox
  Scenario: Assert "Given I enter :value for :field" passes
    Given I am at "form_controls.html"
    When I enter "John" for "Name"

  @test-blackbox
  Scenario: Assert "Given for :field I enter :value" passes
    Given I am at "form_controls.html"
    When for "Name" I enter "Jane"

  @test-blackbox
  Scenario: Assert "Then I should see :text in the :region" fails when text not found in region
    Given some behat configuration
    And scenario steps tagged with "@test-blackbox":
      """
      Given I am at "form_controls.html"
      Then I should see "NONEXISTENT_TEXT_xyz" in the "static content" region
      """
    When I run behat
    Then it should fail with an error:
      """
      The text 'NONEXISTENT_TEXT_xyz' was not found in the region 'static content'
      """

  @test-blackbox
  Scenario: Assert "Then I should not see :text in the :region" fails when text is present in region
    Given some behat configuration
    And scenario steps tagged with "@test-blackbox":
      """
      Given I am at "form_controls.html"
      Then I should not see "Some content text." in the "static content" region
      """
    When I run behat
    Then it should fail with an error:
      """
      The text "Some content text." was found in the region "static content"
      """

  @test-blackbox
  Scenario: Assert region step fails when region not configured
    Given some behat configuration
    And scenario steps tagged with "@test-blackbox":
      """
      Given I am at "form_controls.html"
      Then I should see "something" in the "nonexistent_region" region
      """
    When I run behat
    Then it should fail with a "InvalidArgumentException" exception:
      """
      The "nonexistent_region" region isn't configured!
      """

  @test-blackbox
  Scenario: Assert "Then I should see the link :link in the :region" fails when link not found in region
    Given some behat configuration
    And scenario steps tagged with "@test-blackbox":
      """
      Given I am at "form_controls.html"
      Then I should see the link "Missing" in the "static content" region
      """
    When I run behat
    Then it should fail with an error:
      """
      No link to "Missing" in the "static content" region
      """

  @test-blackbox
  Scenario: Assert "Then I should not see the link :link in the :region" fails when link is present in region
    Given some behat configuration
    And scenario steps tagged with "@test-blackbox":
      """
      Given I am at "form_controls.html"
      Then I should not see the link "Visible link" in the "static content" region
      """
    When I run behat
    Then it should fail with an error:
      """
      Link to "Visible link" in the "static content" region
      """

  @test-blackbox
  Scenario: Assert "Then I should see the heading :heading in the :region" fails when heading not in region
    Given some behat configuration
    And scenario steps tagged with "@test-blackbox":
      """
      Given I am at "form_controls.html"
      Then I should see the heading "Nonexistent Heading" in the "static content" region
      """
    When I run behat
    Then it should fail with an error:
      """
      The heading "Nonexistent Heading" was not found in the "static content" region
      """

  @test-blackbox
  Scenario: Assert "When I click :link in the :region" fails when link not found in region
    Given some behat configuration
    And scenario steps tagged with "@test-blackbox":
      """
      Given I am at "form_controls.html"
      When I click "Nonexistent link" in the "static content" region
      """
    When I run behat
    Then it should fail with an error:
      """
      The link "Nonexistent link" was not found in the region "static content"
      """

  @test-blackbox
  Scenario: Assert "Given I press :button in the :region" fails when button not found in region
    Given some behat configuration
    And scenario steps tagged with "@test-blackbox":
      """
      Given I am at "form_controls.html"
      Given I press "Nonexistent" in the "static footer" region
      """
    When I run behat
    Then it should fail with an error:
      """
      The button 'Nonexistent' was not found in the region 'static footer'
      """

  @test-blackbox
  Scenario: Assert "When I :action details labelled :summary" fails when details not found
    Given some behat configuration
    And scenario steps tagged with "@test-blackbox":
      """
      Given I am at "form_controls.html"
      When I click details labelled "Nonexistent details"
      """
    When I run behat
    Then it should fail with an error:
      """
      Unable to find details
      """
