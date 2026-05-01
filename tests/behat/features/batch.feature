Feature: Drupal Batch API and queue
  As a developer
  I want to create queue items and manage batch operations
  So that I can test background processing behaviour

  @test-drupal @api
  Scenario: Assert "Given the following item is in the system queue:" passes
    Given the following item is in the system queue:
      | name    | test_queue       |
      | data    | {"key": "value"} |
      | created | 1700000000       |
      | expire  | 0                |
