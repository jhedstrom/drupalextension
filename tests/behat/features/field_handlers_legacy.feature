Feature: FieldHandlersLegacyParser
  As a developer
  I want the legacy field parser to remain available for migration
  So that I can keep existing 5.x feature files running until the parser is removed in 6.1

  # Each scenario runs a Behat subprocess against an inline feature that
  # uses the legacy syntax. The subprocess is configured with
  # 'field_parser: legacy' so the deprecated parser handles the values;
  # the parent scenario asserts the subprocess passes.

  @test-drupal @api
  Scenario: Assert legacy positional and named compound syntax pass under field_parser:legacy
    Given some behat configuration
    And the behat configuration uses the legacy field parser
    And scenario steps tagged with "@test-drupal @api":
      """
      Given the following "page" content:
        | title      |
        | Page one   |
        | Page two   |
        | Page three |
      When I am viewing a "post" content with the following fields:
        | title                | Post title                                                                       |
        | body                 | PLACEHOLDER BODY                                                                 |
        | field_post_reference | Page one, Page two                                                               |
        | field_post_links     | Link 1 - http://example.com, Link 2 - http://example.com                         |
        | field_post_address   | country: BE - locality: Brussel - thoroughfare: Louisalaan 1 - postal_code: 1000 |
      Then I should see "Post title"
      And I should see "Page one"
      And I should see "Page two"
      And I should see the link "Link 1"
      And I should see the link "Link 2"
      And I should see "Belgium"
      And I should see "Brussel"
      And I should see "1000"
      And I should see "Louisalaan 1"
      """
    When I run behat with drupal profile
    Then it should pass with:
      """
      1 scenario (1 passed)
      """

  @test-drupal @api
  Scenario: Assert URI-only link value passes under field_parser:legacy
    Given some behat configuration
    And the behat configuration uses the legacy field parser
    And scenario steps tagged with "@test-drupal @api":
      """
      When I am viewing a "post" content with the following fields:
        | title            | Post with URI-only link |
        | field_post_links | http://example.com      |
      Then I should see the link "http://example.com"
      """
    When I run behat with drupal profile
    Then it should pass with:
      """
      1 scenario (1 passed)
      """

  @test-drupal @api
  Scenario: Assert quoted entity reference with compound separator passes under field_parser:legacy
    Given some behat configuration
    And the behat configuration uses the legacy field parser
    And scenario steps tagged with "@test-drupal @api":
      """
      Given the following "page" content:
        | title         |
        | Alpha - Bravo |
      When I am viewing a "post" content with the following fields:
        | title                | Post with ref     |
        | field_post_reference | "Alpha - Bravo"   |
      Then I should see "Alpha - Bravo"
      """
    When I run behat with drupal profile
    Then it should pass with:
      """
      1 scenario (1 passed)
      """

  # The legacy parser still has the silent-split bug for unquoted entity
  # reference titles containing ' - '. This stays a negative test until the
  # legacy parser is removed in 6.1.
  @test-drupal @api
  Scenario: Assert unquoted entity reference with compound separator fails under field_parser:legacy
    Given some behat configuration
    And the behat configuration uses the legacy field parser
    And scenario steps tagged with "@test-drupal @api":
      """
      Given the following "page" content:
        | title         |
        | Alpha - Bravo |
      When I am viewing a "post" content with the following fields:
        | title                | Post with ref |
        | field_post_reference | Alpha - Bravo |
      Then I should see "Alpha - Bravo"
      """
    When I run behat with drupal profile
    Then it should fail with a "Drupal\Core\Database\InvalidQueryException" exception:
      """
      must have an array compatible operator
      """
