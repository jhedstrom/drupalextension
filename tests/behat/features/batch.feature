Feature: BatchContext
  As a developer
  I want to create queue items and manage batch operations
  So that I can test background processing behaviour

  @api @test-drupal
  Scenario: Create an item in the system queue
    Given there is an item in the system queue:
      | name    | test_queue       |
      | data    | {"key": "value"} |
      | created | 1700000000       |
      | expire  | 0                |

  @api @javascript @test-drupal
  Scenario: Wait for batch job to finish
    Given I am at "/behat-test/batch"
    When I press "Run batch"
    And I wait for the batch job to finish
    Then I should see "Batch test completed successfully."
