<?php

declare(strict_types=1);

namespace Drupal\DrupalExtension\Context;

use Behat\MinkExtension\Context\RawMinkContext;
use Drupal\DrupalMailManager;

/**
 * Provides helper methods for interacting with mail.
 */
class RawMailContext extends RawDrupalContext {

  /**
   * The mail manager.
   *
   * @var \Drupal\DrupalMailManagerInterface
   */
  protected $mailManager;

  /**
   * The number of mails received so far in this scenario, for each mail store.
   *
   * @var array
   */
  protected $mailMessageCount = [];

  /**
   * Get the mail manager service that handles stored test mail.
   *
   * @return \Drupal\DrupalMailManagerInterface
   *   The mail manager service.
   */
  protected function getMailManager() {
    // Persist the mail manager between invocations. This is necessary for
    // remembering and reinstating the original mail backend.
    if (is_null($this->mailManager)) {
      $this->mailManager = new DrupalMailManager($this->getDriver());
    }

    return $this->mailManager;
  }

  /**
   * Get collected mail, matching certain specifications.
   *
   * @param array $criteria
   *   Associative array of mail fields and the values to filter by.
   * @param bool $new
   *   Whether to ignore previously seen mail.
   * @param null|int $index
   *   A particular mail to return, e.g. 0 for first or -1 for last.
   * @param string $store
   *   The name of the mail store to get mail from.
   *
   * @return \stdClass[]|\stdClass
   *   An array of mail, each formatted as a Drupal 8
   *   \Drupal\Core\Mail\MailInterface::mail $message array, or a single mail
   *   object if $index is specified.
   */
  protected function getMail(array $criteria = [], bool $new = FALSE, ?int $index = NULL, string $store = 'default') {
    $messages = $this->getMailManager()->getMail($store);
    $previousCount = $this->getMailMessageCount($store);
    $this->mailMessageCount[$store] = count($messages);

    // Ignore previously seen messages.
    if ($new) {
      $messages = array_slice($messages, $previousCount);
    }

    // Filter messages based on $matches; keep only mail where each field
    // mentioned in $filters contains the value specified for that field.
    $messages = array_values(array_filter($messages, fn(array $message): bool => $this->matchMessage($message, $criteria)));

    // Return an individual mail if specified by an index.
    if (is_null($index) || count($messages) === 0) {
      return $messages;
    }

    return array_slice($messages, $index, 1)[0];
  }

  /**
   * Get the number of mails received in a particular mail store.
   *
   * @return int
   *   The number of mails received during this scenario.
   */
  protected function getMailMessageCount(string $store) {
    if (array_key_exists($store, $this->mailMessageCount)) {
      return $this->mailMessageCount[$store];
    }

    return 0;
  }

  /**
   * Determine if a mail meets criteria.
   *
   * @param array $message
   *   The mail message as an associative array of mail fields.
   * @param array $criteria
   *   The criteria: an associative array of mail fields and desired values.
   *
   * @return bool
   *   Whether the mail message matches the criteria.
   */
  protected function matchMessage(array $message, array $criteria): bool {
    // Discard criteria that are just zero-length strings.
    $criteria = array_filter($criteria, strlen(...));

    // For each criteria, check the specified mail field contains the value.
    foreach ($criteria as $field => $value) {
      // Case insensitive.
      if (stripos((string) $message[$field], (string) $value) === FALSE) {
        return FALSE;
      }
    }

    return TRUE;
  }

  /**
   * Compare actual mail with expected mail.
   *
   * @param array $actualMessages
   *   An array of actual mail.
   * @param array $expectedMessages
   *   An array of expected mail.
   */
  protected function compareMessages(array $actualMessages, array $expectedMessages) {
    // Make sure there is the same number of actual and expected.
    $expectedCount = count($expectedMessages);
    $this->assertMessageCount($actualMessages, $expectedCount);

    // For each row of expected mail, check the corresponding actual mail.
    // Make the comparison insensitive to the order mails were sent.
    $actualMessages = $this->sortMessages($actualMessages);
    $expectedMessages = $this->sortMessages($expectedMessages);
    foreach ($expectedMessages as $index => $expectedMailItem) {
      // For each column of the expected, check the field of the actual mail.
      foreach ($expectedMailItem as $fieldName => $fieldValue) {
        $expectedField = [$fieldName => $fieldValue];
        $isMatch = $this->matchMessage($actualMessages[$index], $expectedField);
        if (!$isMatch) {
          throw new \Exception(sprintf("The #%s mail did not have '%s' in its %s field. It had:\n'%s'", $index, $fieldValue, $fieldName, mb_strimwidth((string) $actualMessages[$index][$fieldName], 0, 30, "...")));
        }
      }
    }
  }

  /**
   * Assert there is the expected number of mail messages.
   *
   * @param array $actualMessages
   *   An array of actual mail.
   * @param int $expectedCount
   *   Optional. The number of mails expected.
   */
  protected function assertMessageCount(array $actualMessages, ?int $expectedCount = NULL) {
    $actualCount = count($actualMessages);
    if (is_null($expectedCount)) {
      // If number to expect is not specified, expect more than zero.
      if ($actualCount === 0) {
        throw new \Exception("Expected some mail, but none found.");
      }
    }
    elseif ($expectedCount !== $actualCount) {
      // Prepare a simple list of actual mail.
      $formattedActualMessages = [];
      foreach ($actualMessages as $actualMessage) {
        $formattedActualMessages[] = [
          'to' => $actualMessage['to'],
          'subject' => $actualMessage['subject'],
        ];
      }
      throw new \Exception(sprintf("Expected %s mail, but %s found:\n\n%s", $expectedCount, $actualCount, print_r($formattedActualMessages, TRUE)));
    }
  }

  /**
   * Sort mail by to, subject and body.
   *
   * @param array $messages
   *   An array of mail messages to sort.
   *
   * @return array
   *   The same mail, but sorted.
   */
  protected function sortMessages(array $messages): array {
    foreach (array_keys($messages) as $key) {
      $messages[$key] += ['to' => '', 'subject' => '', 'body' => ''];
    }

    $to = array_column($messages, 'to');
    $subject = array_column($messages, 'subject');
    $body = array_column($messages, 'body');
    array_multisort($to, SORT_ASC, $subject, SORT_ASC, $body, SORT_ASC, $messages);

    return $messages;
  }

  /**
   * Get the mink context, so we can visit pages using the mink session.
   */
  protected function getMinkContext(): object {
    $minkContext = $this->getContext(RawMinkContext::class);

    if ($minkContext === FALSE) {
      throw new \Exception('No mink context found.');
    }

    return $minkContext;
  }

}
