<?php

namespace Drupal\DrupalExtension\Context;

use Behat\MinkExtension\Context\RawMinkContext;

/**
 * Extensions to the Mink Extension.
 */
class BatchContext extends RawMinkContext
{

  /**
   * Wait for the Batch API to finish.
   *
   * Wait until the id="updateprogress" element is gone,
   * or timeout after 3 minutes (180,000 ms).
   *
   * @Given /^I wait for the batch job to finish$/
   */
    public function iWaitForTheBatchJobToFinish()
    {
        $this->getSession()->wait(180000, 'jQuery("#updateprogress").length === 0');
    }

  /**
   * Creates a queue item. Defaults inputs if none are available.
   *
   * Expects the `data` to be a json encoded string.
   *
   * @Given there is an item in the system queue:
   */
    public function thereIsAnItemInTheSystemQueue(TableNode $table)
    {
        // Gather the data.
        $fields = $table->getRowsHash();

        // Default data field separately since this is longish.
        if (empty($fields['data'])) {
            $fields['data'] = json_encode([]);
        }

        // @see SystemQueue::createItem().
        $query = db_insert('queue')
        ->fields([
            'name' => $fields['name'] ?: user_password(),
            'data' => serialize(json_decode($fields['data'])),
            'created' => $fields['created'] ?: REQUEST_TIME,
            'expire' => $fields['expire'] ?: 0,
        ]);
        if (!$query->execute()) {
            throw new Exception('Unable to create the queue item.');
        }
    }
}
