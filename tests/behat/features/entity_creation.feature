Feature: Generic entity creation
  As a developer
  I want to create entities of arbitrary content entity types
  So that I can set up fixtures for custom and contrib entities without bespoke steps

  @test-drupal @api
  Scenario: Assert "Given the following :type entities:" creates entities of the given type
    Given the following "behat_test_thing" entities:
      | title       | status |
      | First thing | 1      |
      | Second one  | 0      |
    When I run drush "sql:query" "'SELECT COUNT(*) FROM behat_test_thing'"
    Then the drush output should contain "2"

  @test-drupal @api
  Scenario: Assert "Given the following :type entities:" removes entities after the scenario
    # Outer scenario invokes an inner scenario that creates entities; after
    # the inner run completes, 'cleanEntities()' should have deleted them.
    Given some behat configuration
    And a file named "features/stub.feature" with:
      """
      Feature: Inner stub

        @test-drupal @api
        Scenario: Create entities
          Given the following "behat_test_thing" entities:
            | title           |
            | Cleanup probe A |
            | Cleanup probe B |
      """
    When I run behat with drupal profile
    Then it should pass
    And I run drush "sql:query" "'SELECT COUNT(*) FROM behat_test_thing'"
    Then the drush output should contain "0"

  @test-drupal @api
  Scenario: Assert "Given the following :type entities:" fails for an unknown entity type
    Given some behat configuration
    And scenario steps tagged with "@test-drupal @api":
      """
      Given the following "no_such_entity" entities:
        | title |
        | x     |
      """
    When I run behat with drupal profile
    Then it should fail with a "Drupal\Component\Plugin\Exception\PluginNotFoundException" exception:
      """
      The "no_such_entity" entity type does not exist.
      """

  @test-drupal @api
  Scenario: Assert "Before/AfterEntityCreate" hooks fire for every entity create path
    # A single Before/AfterEntityCreate handler registered in FeatureContext
    # must observe all four create paths - user, term, node, and the generic
    # entityCreate() path - within one scenario.
    Given the following users:
      | name        |
      | Hook probe  |
    And the following "tags" terms:
      | name      |
      | Hook tag  |
    And the following "article" content:
      | title         |
      | Hook article  |
    And the following "behat_test_thing" entities:
      | title      |
      | Hook thing |
    Then the generic entity create hook should have fired for:
      | entity_type      |
      | user             |
      | taxonomy_term    |
      | node             |
      | behat_test_thing |
