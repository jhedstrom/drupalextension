<?php

namespace Drupal\DrupalExtension\Context;

use Behat\Gherkin\Node\TableNode;

/**
 * Provides pre-built step definitions for interacting with mail.
 */
class MailContext extends RawMailContext {

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
