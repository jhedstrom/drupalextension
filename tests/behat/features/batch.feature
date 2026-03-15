Feature: BatchContext
  As a developer
  I want to create queue items and manage batch operations
  So that I can test background processing behaviour

  @test-drupal @api
  Scenario: Create an item in the system queue
    Given there is an item in the system queue:
      | name    | test_queue       |
      | data    | {"key": "value"} |
      | created | 1700000000       |
      | expire  | 0                |
