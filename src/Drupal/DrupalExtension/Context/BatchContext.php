<?php

declare(strict_types=1);

namespace Drupal\DrupalExtension\Context;

use Behat\Step\Given;
use Behat\Gherkin\Node\TableNode;
use Behat\MinkExtension\Context\RawMinkContext;

/**
 * Extensions to the Mink Extension.
 */
class BatchContext extends RawMinkContext {

  /**
   * Wait for the Batch API to finish.
   *
   * Wait until the id="updateprogress" element is gone,
   * or timeout after 3 minutes (180,000 ms).
   *
   * @code
   * Given I wait for the batch job to finish
   * @endcode
   */
  #[Given('I wait for the batch job to finish')]
  public function iWaitForTheBatchJobToFinish(): void {
    $this->getSession()->wait(180000, 'jQuery("#updateprogress").length === 0');
  }

  /**
   * Creates a queue item. Defaults inputs if none are available.
   *
   * Expects the `data` to be a json encoded string.
   *
   * @code
   *   Given there is an item in the system queue:
   *     | name    | my_queue              |
   *     | data    | {"key":"value"}       |
   *     | created | 1700000000            |
   *     | expire  | 0                     |
   * @endcode
   */
  #[Given('there is an item in the system queue:')]
  public function thereIsAnItemInTheSystemQueue(TableNode $table): void {
    // Gather the data.
    $fields = $table->getRowsHash();

    // Default data field separately since this is longish.
    if (empty($fields['data'])) {
      $fields['data'] = json_encode([]);
    }

    // @see SystemQueue::createItem().
    /** @var \Drupal\Core\Database\Connection $connection */
    $connection = \Drupal::service('database');
    $query = $connection->insert('queue')
      ->fields([
        'name' => $fields['name'] ?: \Drupal::service('password_generator')->generate(),
        'data' => serialize(json_decode((string) $fields['data'])),
        'created' => $fields['created'] ?: $_SERVER['REQUEST_TIME'],
        'expire' => $fields['expire'] ?: 0,
      ]);

    if (!$query->execute()) {
      throw new \Exception('Unable to create the queue item.');
    }
  }

}
