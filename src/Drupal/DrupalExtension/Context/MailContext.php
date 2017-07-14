<?php

namespace Drupal\DrupalExtension\Context;

use Drupal\DrupalMailManager;
use Behat\Gherkin\Node\TableNode;

/**
 * Provides pre-built step definitions for interacting with Drupal.
 */
class MailContext extends RawDrupalContext {

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
  protected function getMail($matches = [], $new = FALSE, $index = NULL, $store = 'default') {
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
    if (is_null($index)) {
      return $mail;
    }
    else {
      return array_slice($mail, $index, 1)[0];
    }
  }

  /**
   * Get the number of mails received in a particular mail store.
   *
   * @return int
   *   The number of mails received during this scenario.
   */
  protected function getMailCount($store) {
    if (array_key_exists($store, $this->mailCount)) {
      $count = $this->mailCount[$store];
    }
    else {
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
  protected function matchesMail($mail = [], $matches = []) {
    // Discard criteria that are just zero-length strings.
    $matches = array_filter($matches, 'strlen');
    // For each criteria, check the specified mail field contains the value.
    foreach($matches as $field => $value) {
      if (strpos($mail[$field], $value) === FALSE) {
        return FALSE;
      }
    }
    return TRUE;
  }

  /**
   * Compare actual mail with expected mail.
   *
   * @param array $actualMail
   *   An array of actual mail.
   * @param array $expectedMail
   *   An array of expected mail.
   */
  protected function compareMail($actualMail, $expectedMail) {
    // Make sure there is the same number of actual and expected
    $actualCount = count($actualMail);
    $expectedCount = count($expectedMail);
    if ($expectedCount !== $actualCount) {
      throw new \Exception(sprintf('%s mail expected, but %s found.', $expectedCount, $actualCount));
    }

    // For each row of expected mail, check the corresponding actual mail.
    foreach ($expectedMail as $index => $expectedMailItem) {
      // For each column of the expected, check the field of the actual mail.
      foreach ($expectedMailItem as $fieldName => $fieldValue) {
        $expectedField = [$fieldName => $fieldValue];
        $match = $this->matchesMail($actualMail[$index], $expectedField);
        if (!$match) {
          throw new \Exception(sprintf('The #%s mail did not have %s in its %s field. ', $index, $fieldName, $fieldValue));
        }
      }
    }
  }

  /**
   * Get the mink context, so we can visit pages using the mink session.
   */
  protected function getMinkContext() {
    $minkContext =  $this->getContext('\Behat\MinkExtension\Context\RawMinkContext');
    if ($minkContext === FALSE) {
      throw new \Exception(sprintf('No mink context found.'));
    }
    return $minkContext;
  }

  /**
   * By default, prevent mail from being actually sent out during tests.
   *
   * @BeforeScenario
   */
  public function disableMail() {
    $this->getMailManager()->disableMail();
    // Always reset mail count, in case the default mail manager is being used
    // which enables mail collecting automatically when mail is disabled, making
    //the use of the @mail tag optional in this case.
    $this->mailCount = [];
  }

  /**
   * Restore mail sending.
   *
   * @AfterScenario
   */
  public function enableMail() {
    $this->getMailManager()->enableMail();
  }

  /**
   * Allow opting in to actually sending mail out.
   *
   * @BeforeScenario @sendmail @sendemail
   */
  public function sendMail() {
    $this->getMailManager()->enableMail();
  }

  /**
   * Allow opting in to mail collection. When using the default mail manager 
   * service, it is not necessary to use this tag.
   *
   * @BeforeScenario @mail @email
   */
  public function collectMail() {
    $this->getMailManager()->startCollectingMail();
  }

  /**
   * Stop collecting mail at scenario end.
   *
   * @AfterScenario @mail @email
   */
  public function stopCollectingMail() {
    $this->getMailManager()->stopCollectingMail();
  }

  /**
   * This is mainly useful for testing this context.
   * 
   * @When Drupal sends a/an (e)mail:
   */
  public function DrupalSendsMail(TableNode $fields) {
    $mail = [
      'body' => $this->getRandom()->name(255),
      'subject' => $this->getRandom()->name(20),
      'to' => $this->getRandom()->name(10) . '@anonexample.com',
      'langcode' => '',
    ];
    foreach ($fields->getRowsHash() as $field => $value) {
      $mail[$field] = $value;
    }
    $this->getDriver()->sendMail($mail['body'], $mail['subject'], $mail['to'], $mail['langcode']);
  }

  /**
   * Check all mail sent during the scenario.
   * 
   * @Then (e)mail(s) has/have been sent:
   * @Then (e)mail(s) has/have been sent to :to:
   */
  public function mailHasBeenSent(TableNode $expectedMailTable, $to = NULL) {
    $expectedMail = $expectedMailTable->getHash();
    $matches = [];
    if (!is_null($to)) {
      $matches = ['to' => $to];
    }
    $actualMail = $this->getMail($matches);
    $this->compareMail($actualMail, $expectedMail);
  }

  /**
   * Check mail sent since the last step that checked mail.
   * 
   * @Then new (e)mail(s) is/are sent:
   * @Then new (e)mail(s) is/are sent to :to:
   */
  public function newMailIsSent(TableNode $expectedMailTable, $to = NULL) {
    $expectedMail = $expectedMailTable->getHash();
    $matches = [];
    if (!is_null($to)) {
      $matches = ['to' => $to];
    }
    $actualMail = $this->getMail($matches, TRUE);
    $this->compareMail($actualMail, $expectedMail);
  }

  /**
   * Check all mail sent during the scenario.
   *
   * @Then no (e)mail(s) has/have been sent
   * @Then no (e)mail(s) has/have been sent to :to
   */
  public function noMailHasBeenSent($to = NULL) {
    $matches = [];
    if (!is_null($to)) {
      $matches = ['to' => $to];
    }
    $actualMail = $this->getMail($matches);
    $this->compareMail($actualMail, []);
  }

  /**
   * Check mail sent since the last step that checked mail.
   *
   * @Then no new (e)mail(s) is/are sent
   * @Then no new (e)mail(s) is/are sent to :to
   */
  public function noNewMailIsSent($to = NULL) {
    $matches = [];
    if (!is_null($to)) {
      $matches = ['to' => $to];
    }
    $actualMail = $this->getMail($matches, TRUE);
    $this->compareMail($actualMail, []);
  }
  
  /**
   * @When I follow the link to :urlFragment from the (e)mail
   * @When I follow the link to :urlFragment from the (e)mail to :to
   * @When I follow the link to :urlFragment from the (e)mail with the subject :subject
   * @When I follow the link to :urlFragment from the (e)mail to :to with the subject :subject
   */
  public function followLinkInMail($urlFragment, $to = '', $subject = '') {
    // Get the mail
    $matches = ['to' => $to, 'subject' => $subject];
    $mail = $this->getMail($matches, FALSE, -1);
    $body = $mail['body'];

    // Find web URLs in the mail
    $urlPattern = '`.*?((http|https)://[\w#$&+,\/:;=?@.-]+)[^\w#$&+,\/:;=?@.-]*?`i';
    if (preg_match_all($urlPattern, $body, $urls)) {
      // Visit the first url that contains the desired fragment.
      foreach ($urls[1] as $url) {
        $match = (strpos(strtolower($url), strtolower($urlFragment)) !== FALSE);
        if ($match) {
          $this->getMinkContext()->visitPath($url);
          return;
        }
      }
      throw new \Exception(sprintf('No URL in mail body contained "%s".', $urlFragment));
    }
    else {
      throw new \Exception('No URL found in mail body.');
    }
  }

}
