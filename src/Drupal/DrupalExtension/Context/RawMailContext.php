<?php

namespace Drupal\DrupalExtension\Context;

use Drupal\DrupalMailManager;

/**
 * Provides helper methods for interacting with mail.
 */
class RawMailContext extends RawDrupalContext
{

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
    protected $mailCount = [];

  /**
   * Get the mail manager service that handles stored test mail.
   *
   * @return \Drupal\DrupalMailManagerInterface
   *   The mail manager service.
   */
    protected function getMailManager()
    {
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
   * @param array $matches
   *   Associative array of mail fields and the values to filter by.
   * @param bool $new
   *   Whether to ignore previously seen mail.
   * @param null|int $index
   *   A particular mail to return, e.g. 0 for first or -1 for last.
   * @param string $store
   *   The name of the mail store to get mail from.
   *
   * @return \stdClass[]
   *   An array of mail, each formatted as a Drupal 8
   * \Drupal\Core\Mail\MailInterface::mail $message array.
   */
    protected function getMail($matches = [], $new = false, $index = null, $store = 'default')
    {
        $mail = $this->getMailManager()->getMail($store);
        $previousMailCount = $this->getMailCount($store);
        $this->mailCount[$store] = count($mail);

        // Ignore previously seen mail.
        if ($new) {
            $mail = array_slice($mail, $previousMailCount);
        }

        // Filter mail based on $matches; keep only mail where each field mentioned
        // in $matches contains the value specified for that field.
        $mail = array_values(array_filter($mail, function ($singleMail) use ($matches) {
            return ($this->matchesMail($singleMail, $matches));
        }));

        // Return an individual mail if specified by an index.
        if (is_null($index) || count($mail) === 0) {
            return $mail;
        } else {
            return array_slice($mail, $index, 1)[0];
        }
    }

  /**
   * Get the number of mails received in a particular mail store.
   *
   * @return int
   *   The number of mails received during this scenario.
   */
    protected function getMailCount($store)
    {
        if (array_key_exists($store, $this->mailCount)) {
            $count = $this->mailCount[$store];
        } else {
            $count = 0;
        }
        return $count;
    }

  /**
   * Determine if a mail meets criteria.
   *
   * @param array $mail
   *   The mail, as an array of mail fields.
   * @param array $matches
   *   The criteria: an associative array of mail fields and desired values.
   *
   * @return bool
   *   Whether the mail matches the criteria.
   */
    protected function matchesMail($mail = [], $matches = [])
    {
        // Discard criteria that are just zero-length strings.
        $matches = array_filter($matches, 'strlen');
        // For each criteria, check the specified mail field contains the value.
        foreach ($matches as $field => $value) {
            // Case insensitive.
            if (stripos($mail[$field], $value) === false) {
                return false;
            }
        }
        return true;
    }

  /**
   * Compare actual mail with expected mail.
   *
   * @param array $actualMail
   *   An array of actual mail.
   * @param array $expectedMail
   *   An array of expected mail.
   */
    protected function compareMail($actualMail, $expectedMail)
    {
        // Make sure there is the same number of actual and expected.
        $expectedCount = count($expectedMail);
        $this->assertMailCount($actualMail, $expectedCount);

        // For each row of expected mail, check the corresponding actual mail.
        // Make the comparison insensitive to the order mails were sent.
        $actualMail = $this->sortMail($actualMail);
        $expectedMail = $this->sortMail($expectedMail);
        foreach ($expectedMail as $index => $expectedMailItem) {
            // For each column of the expected, check the field of the actual mail.
            foreach ($expectedMailItem as $fieldName => $fieldValue) {
                $expectedField = [$fieldName => $fieldValue];
                $match = $this->matchesMail($actualMail[$index], $expectedField);
                if (!$match) {
                    throw new \Exception(sprintf("The #%s mail did not have '%s' in its %s field. It had:\n'%s'", $index, $fieldValue, $fieldName, mb_strimwidth($actualMail[$index][$fieldName], 0, 30, "...")));
                }
            }
        }
    }

  /**
   * Assert there is the expected number of mails, or that there are some mails
   * if the exact number expected is not specified.
   *
   * @param array $actualMail
   *   An array of actual mail.
   * @param int $expectedCount
   *   Optional. The number of mails expected.
   */
    protected function assertMailCount($actualMail, $expectedCount = null)
    {
        $actualCount = count($actualMail);
        if (is_null($expectedCount)) {
            // If number to expect is not specified, expect more than zero.
            if ($actualCount === 0) {
                throw new \Exception("Expected some mail, but none found.");
            }
        } else {
            if ($expectedCount != $actualCount) {
                // Prepare a simple list of actual mail.
                $prettyActualMail = [];
                foreach ($actualMail as $singleActualMail) {
                    $prettyActualMail[] = [
                        'to' => $singleActualMail['to'],
                        'subject' => $singleActualMail['subject'],
                    ];
                }
                throw new \Exception(sprintf("Expected %s mail, but %s found:\n\n%s", $expectedCount, $actualCount, print_r($prettyActualMail, true)));
            }
        }
    }
  
  /**
   * Sort mail by to, subject and body.
   *
   * @param array $mail
   *   An array of mail to sort.
   *
   * @return array
   *   The same mail, but sorted.
   */
    protected function sortMail($mail)
    {
        // Can't sort an empty array.
        if (count($mail) === 0) {
            return [];
        }

        // To, subject and body keys must be present.
        // Empty strings are ignored when matching so adding them is harmless.
        foreach ($mail as $key => $row) {
            if (!array_key_exists('to', $row)) {
                $mail[$key]['to'] = '';
            }
            if (!array_key_exists('subject', $row)) {
                $mail[$key]['subject'] = '';
            }
            if (!array_key_exists('body', $row)) {
                $mail[$key]['body'] = '';
            }
        }

        // Obtain a list of columns.
        foreach ($mail as $key => $row) {
            if (array_key_exists('to', $row)) {
                $to[$key] = $row['to'];
            }
            if (array_key_exists('subject', $row)) {
                $subject[$key] = $row['subject'];
            }
            if (array_key_exists('body', $row)) {
                $body[$key] = $row['body'];
            }
        }

        // Add $mail as the last parameter, to sort by the common key.
        array_multisort($to, SORT_ASC, $subject, SORT_ASC, $body, SORT_ASC, $mail);
        return $mail;
    }

  /**
   * Get the mink context, so we can visit pages using the mink session.
   */
    protected function getMinkContext()
    {
        $minkContext =  $this->getContext('\Behat\MinkExtension\Context\RawMinkContext');
        if ($minkContext === false) {
            throw new \Exception(sprintf('No mink context found.'));
        }
        return $minkContext;
    }
}
