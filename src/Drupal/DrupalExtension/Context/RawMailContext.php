<?php

declare(strict_types=1);

namespace Drupal\DrupalExtension\Context;

use Behat\MinkExtension\Context\RawMinkContext;
use Drupal\Driver\Capability\MailCapabilityInterface;
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
      $driver = $this->getDriver();

      if (!$driver instanceof MailCapabilityInterface) {
        throw new \RuntimeException(sprintf('The active Drupal driver "%s" does not support mail collection.', $driver::class));
      }

      $this->mailManager = new DrupalMailManager($driver);
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
   * @return array<int, array<string, mixed>>|array<string, mixed>
   *   An array of mail messages keyed by index, or a single mail message
   *   array when '$index' is specified. Each item follows Drupal's
   *   'MailInterface::mail()' shape ('to', 'subject', 'body', etc.).
   */
  protected function getMail(array $criteria = [], bool $new = FALSE, ?int $index = NULL, string $store = 'default') {
    $messages = $this->getMailManager()->getMail($store);
    $previous_count = $this->getMailMessageCount($store);
    $this->mailMessageCount[$store] = count($messages);

    // Ignore previously seen messages.
    if ($new) {
      $messages = array_slice($messages, $previous_count);
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
    $expected_count = count($expectedMessages);
    $this->assertMessageCount($actualMessages, $expected_count);

    // For each row of expected mail, check the corresponding actual mail.
    // Make the comparison insensitive to the order mails were sent.
    $actualMessages = $this->sortMessages($actualMessages);
    $expectedMessages = $this->sortMessages($expectedMessages);
    foreach ($expectedMessages as $index => $expected_mail_item) {
      // For each column of the expected, check the field of the actual mail.
      foreach ($expected_mail_item as $field_name => $field_value) {
        $expected_field = [$field_name => $field_value];
        $is_match = $this->matchMessage($actualMessages[$index], $expected_field);
        if (!$is_match) {
          throw new \Exception(sprintf("The #%s mail did not have '%s' in its %s field. It had:\n'%s'", $index, $field_value, $field_name, mb_strimwidth((string) $actualMessages[$index][$field_name], 0, 30, "...")));
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
    $actual_count = count($actualMessages);
    if (is_null($expectedCount)) {
      // If number to expect is not specified, expect more than zero.
      if ($actual_count === 0) {
        throw new \Exception("Expected some mail, but none found.");
      }
    }
    elseif ($expectedCount !== $actual_count) {
      // Prepare a simple list of actual mail.
      $formatted_actual_messages = [];
      foreach ($actualMessages as $actual_message) {
        $formatted_actual_messages[] = [
          'to' => $actual_message['to'],
          'subject' => $actual_message['subject'],
        ];
      }
      throw new \Exception(sprintf("Expected %s mail, but %s found:\n\n%s", $expectedCount, $actual_count, print_r($formatted_actual_messages, TRUE)));
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
    $mink_context = $this->getContext(RawMinkContext::class);

    if ($mink_context === FALSE) {
      throw new \Exception('No mink context found.');
    }

    return $mink_context;
  }

}
