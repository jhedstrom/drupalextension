<?php

declare(strict_types=1);

namespace Drupal\DrupalExtension\Context\Traits;

use Behat\Gherkin\Node\TableNode;
use Behat\Step\Given;

/**
 * Drupal-specific batch and queue helpers.
 *
 * Both steps are Drupal-aware: 'iWaitForTheBatchJobToFinish' polls the
 * '#updateprogress' element rendered by Drupal's Batch API, and
 * 'thereIsAnItemInTheSystemQueue' writes directly to the 'queue' table
 * managed by Drupal's SystemQueue. The trait is therefore consumed by the
 * Drupal-aware 'DrupalContext' rather than a Mink-only context, which keeps
 * '\Drupal::' calls behind the API driver and avoids silent failures under
 * Blackbox/Drush drivers.
 *
 * The host class is expected to extend 'RawDrupalContext' so '$this->getSession()'
 * is available and the Drupal kernel is bootstrapped.
 */
trait BatchTrait {

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
   *   Given the following item is in the system queue:
   *     | name    | my_queue              |
   *     | data    | {"key":"value"}       |
   *     | created | 1700000000            |
   *     | expire  | 0                     |
   * @endcode
   */
  #[Given('the following item is in the system queue:')]
  public function thereIsAnItemInTheSystemQueue(TableNode $table): void {
    $fields = $table->getRowsHash();

    if (empty($fields['data'])) {
      $fields['data'] = json_encode([]);
    }

    // @see SystemQueue::createItem().
    /** @var \Drupal\Core\Database\Connection $connection */
    $connection = \Drupal::service('database');
    /** @var \Drupal\Core\Password\PasswordGeneratorInterface $password_generator */
    $password_generator = \Drupal::service('password_generator');
    $data = is_string($fields['data']) ? $fields['data'] : (string) json_encode($fields['data']);
    $query = $connection->insert('queue')
      ->fields([
        'name' => (($fields['name'] ?? '') !== '') ? $fields['name'] : $password_generator->generate(),
        'data' => serialize(json_decode($data)),
        'created' => $fields['created'] ?: $_SERVER['REQUEST_TIME'],
        'expire' => $fields['expire'] ?: 0,
      ]);

    if (!$query->execute()) {
      throw new \RuntimeException('Unable to create the queue item.');
    }
  }

}
