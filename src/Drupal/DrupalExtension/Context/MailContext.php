<?php

declare(strict_types=1);

namespace Drupal\DrupalExtension\Context;

use Behat\Hook\BeforeScenario;
use Behat\Hook\AfterScenario;
use Behat\Mink\Exception\ExpectationException;
use Behat\Step\When;
use Behat\Step\Then;
use Behat\Behat\Hook\Scope\ScenarioScope;
use Behat\Gherkin\Node\TableNode;
use Drupal\Driver\Capability\MailCapabilityInterface;

/**
 * Provides pre-built step definitions for interacting with mail.
 */
class MailContext extends RawMailContext {

  /**
   * By default, prevent mail from being actually sent out during tests.
   */
  #[BeforeScenario]
  public function disableMail(ScenarioScope $event): void {
    if ($this->hasSendMailTag($event) || !$this->getDriver() instanceof MailCapabilityInterface) {
      return;
    }

    $this->getMailManager()->disableMail();
    // Always reset mail count, in case the default mail manager is being used
    // which enables mail collecting automatically when mail is disabled, making
    // the use of the @mail tag optional in this case.
    $this->mailMessageCount = [];
  }

  /**
   * Restore mail sending.
   */
  #[AfterScenario]
  public function enableMail(ScenarioScope $event): void {
    if ($this->hasSendMailTag($event) || !$this->getDriver() instanceof MailCapabilityInterface) {
      return;
    }

    $this->getMailManager()->enableMail();
  }

  /**
   * Checks if the scenario has the @sendmail or @sendemail tag.
   */
  protected function hasSendMailTag(ScenarioScope $event): bool {
    $tags = array_merge($event->getFeature()->getTags(), $event->getScenario()->getTags());
    return in_array('sendmail', $tags) || in_array('sendemail', $tags);
  }

  /**
   * Allow opting in to mail collection.
   *
   * When using the default mail manager service, it is not necessary to use
   * this tag.
   */
  #[BeforeScenario('@mail,@email')]
  public function collectMail(): void {
    $this->getMailManager()->startCollectingMail();
  }

  /**
   * Stop collecting mail at scenario end.
   */
  #[AfterScenario('@mail,@email')]
  public function stopCollectingMail(): void {
    $this->getMailManager()->stopCollectingMail();
  }

  /**
   * Send a mail through the active Drupal driver.
   *
   * @code
   *   When I send the following mail:
   *     | to      | user@example.com |
   *     | subject | Test mail        |
   *     | body    | Hello world      |
   * @endcode
   */
  #[When('I send the following mail:')]
  public function iSendMail(TableNode $fields): void {
    $this->sendMailFromTable($fields);
  }

  /**
   * Send an email through the active Drupal driver.
   *
   * @code
   *   When I send the following email:
   *     | to      | user@example.com |
   *     | subject | Test email       |
   *     | body    | Hello world      |
   * @endcode
   */
  #[When('I send the following email:')]
  public function iSendEmail(TableNode $fields): void {
    $this->sendMailFromTable($fields);
  }

  /**
   * Assert mail has been sent during the scenario.
   *
   * @code
   *   Then the following mail should have been sent:
   *     | to               | body                |
   *     | user@example.com | Welcome to the site |
   * @endcode
   */
  #[Then('the following (e)mail(s) should have been sent:')]
  public function mailAssertHasBeenSent(TableNode $expectedMailTable): void {
    $this->assertMailMatches($expectedMailTable, '', '');
  }

  /**
   * Assert mail has been sent to a recipient during the scenario.
   *
   * @code
   *   Then the following mail should have been sent to "user@example.com":
   *     | body                |
   *     | Welcome to the site |
   * @endcode
   */
  #[Then('the following (e)mail(s) should have been sent to :to:')]
  public function mailAssertHasBeenSentTo(string $to, TableNode $expectedMailTable): void {
    $this->assertMailMatches($expectedMailTable, $to, '');
  }

  /**
   * Assert mail with a subject has been sent during the scenario.
   *
   * @code
   *   Then the following mail should have been sent with the subject "Welcome":
   *     | body                |
   *     | Welcome to the site |
   * @endcode
   */
  #[Then('the following (e)mail(s) should have been sent with the subject :subject:')]
  public function mailAssertHasBeenSentWithSubject(string $subject, TableNode $expectedMailTable): void {
    $this->assertMailMatches($expectedMailTable, '', $subject);
  }

  /**
   * Assert mail to a recipient with a subject has been sent.
   *
   * @code
   *   Then the following mail should have been sent to "user@example.com" with the subject "Welcome":
   *     | body                |
   *     | Welcome to the site |
   * @endcode
   */
  #[Then('the following (e)mail(s) should have been sent to :to with the subject :subject:')]
  public function mailAssertHasBeenSentToWithSubject(string $to, string $subject, TableNode $expectedMailTable): void {
    $this->assertMailMatches($expectedMailTable, $to, $subject);
  }

  /**
   * Assert new mail has been sent since the last mail check.
   *
   * @code
   *   Then the following new mail should have been sent:
   *     | subject   |
   *     | Greetings |
   * @endcode
   */
  #[Then('the following new (e)mail(s) should have been sent:')]
  public function newMailAssertIsSent(TableNode $expectedMailTable): void {
    $this->assertNewMailMatches($expectedMailTable, '', '');
  }

  /**
   * Assert new mail to a recipient has been sent since the last mail check.
   *
   * @code
   *   Then the following new mail should have been sent to "user@example.com":
   *     | subject   |
   *     | Greetings |
   * @endcode
   */
  #[Then('the following new (e)mail(s) should have been sent to :to:')]
  public function newMailAssertIsSentTo(string $to, TableNode $expectedMailTable): void {
    $this->assertNewMailMatches($expectedMailTable, $to, '');
  }

  /**
   * Assert new mail with a subject has been sent since the last mail check.
   *
   * @code
   *   Then the following new mail should have been sent with the subject "Greetings":
   *     | body  |
   *     | Hello |
   * @endcode
   */
  #[Then('the following new (e)mail(s) should have been sent with the subject :subject:')]
  public function newMailAssertIsSentWithSubject(string $subject, TableNode $expectedMailTable): void {
    $this->assertNewMailMatches($expectedMailTable, '', $subject);
  }

  /**
   * Assert new mail to a recipient with a subject has been sent.
   *
   * @code
   *   Then the following new mail should have been sent to "user@example.com" with the subject "Greetings":
   *     | body  |
   *     | Hello |
   * @endcode
   */
  #[Then('the following new (e)mail(s) should have been sent to :to with the subject :subject:')]
  public function newMailAssertIsSentToWithSubject(string $to, string $subject, TableNode $expectedMailTable): void {
    $this->assertNewMailMatches($expectedMailTable, $to, $subject);
  }

  /**
   * Assert the count of mails sent during the scenario.
   *
   * @code
   * Then there should be a total of no mails sent
   * Then there should be a total of 2 mails sent
   * @endcode
   */
  #[Then('there should be a total of :count (e)mail(s) sent')]
  public function mailCountAssertEquals(string $count): void {
    $this->assertMailCount($count, '', '');
  }

  /**
   * Assert the count of mails sent to a recipient during the scenario.
   *
   * @code
   * Then there should be a total of no mails sent to "user@example.com"
   * Then there should be a total of 2 mails sent to "user@example.com"
   * @endcode
   */
  #[Then('there should be a total of :count (e)mail(s) sent to :to')]
  public function mailCountAssertEqualsForRecipient(string $count, string $to): void {
    $this->assertMailCount($count, $to, '');
  }

  /**
   * Assert the count of mails sent with a subject during the scenario.
   *
   * @code
   * Then there should be a total of no mails sent with the subject "Welcome"
   * Then there should be a total of 1 mail sent with the subject "Welcome"
   * @endcode
   */
  #[Then('there should be a total of :count (e)mail(s) sent with the subject :subject')]
  public function mailCountAssertEqualsForSubject(string $count, string $subject): void {
    $this->assertMailCount($count, '', $subject);
  }

  /**
   * Assert the count of mails sent to a recipient with a subject.
   *
   * @code
   * Then there should be a total of no mails sent to "user@example.com" with the subject "Welcome"
   * Then there should be a total of 1 mail sent to "user@example.com" with the subject "Welcome"
   * @endcode
   */
  #[Then('there should be a total of :count (e)mail(s) sent to :to with the subject :subject')]
  public function mailCountAssertEqualsForRecipientAndSubject(string $count, string $to, string $subject): void {
    $this->assertMailCount($count, $to, $subject);
  }

  /**
   * Assert the count of new mails sent since the last mail check.
   *
   * @code
   * Then there should be a total of no new mails sent
   * Then there should be a total of 1 new mail sent
   * @endcode
   */
  #[Then('there should be a total of :count new (e)mail(s) sent')]
  public function newMailCountAssertEquals(string $count): void {
    $this->assertNewMailCount($count, '', '');
  }

  /**
   * Assert the count of new mails sent to a recipient since the last check.
   *
   * @code
   * Then there should be a total of no new mails sent to "user@example.com"
   * Then there should be a total of 1 new mail sent to "user@example.com"
   * @endcode
   */
  #[Then('there should be a total of :count new (e)mail(s) sent to :to')]
  public function newMailCountAssertEqualsForRecipient(string $count, string $to): void {
    $this->assertNewMailCount($count, $to, '');
  }

  /**
   * Assert the count of new mails sent with a subject since the last check.
   *
   * @code
   * Then there should be a total of no new mails sent with the subject "Welcome"
   * Then there should be a total of 1 new mail sent with the subject "Welcome"
   * @endcode
   */
  #[Then('there should be a total of :count new (e)mail(s) sent with the subject :subject')]
  public function newMailCountAssertEqualsForSubject(string $count, string $subject): void {
    $this->assertNewMailCount($count, '', $subject);
  }

  /**
   * Assert the count of new mails sent to a recipient with a subject.
   *
   * @code
   * Then there should be a total of no new mails sent to "user@example.com" with the subject "Welcome"
   * Then there should be a total of 1 new mail sent to "user@example.com" with the subject "Welcome"
   * @endcode
   */
  #[Then('there should be a total of :count new (e)mail(s) sent to :to with the subject :subject')]
  public function newMailCountAssertEqualsForRecipientAndSubject(string $count, string $to, string $subject): void {
    $this->assertNewMailCount($count, $to, $subject);
  }

  /**
   * Follow a link from a mail body.
   *
   * @code
   * When I follow the link to "user/reset" from the mail
   * @endcode
   */
  #[When('I follow the link to :urlFragment from the mail')]
  public function iFollowLinkInMail(string $urlFragment): void {
    $this->followLinkFromMail($urlFragment, '', '');
  }

  /**
   * Follow a link from an email body.
   *
   * @code
   * When I follow the link to "user/reset" from the email
   * @endcode
   */
  #[When('I follow the link to :urlFragment from the email')]
  public function iFollowLinkInEmail(string $urlFragment): void {
    $this->followLinkFromMail($urlFragment, '', '');
  }

  /**
   * Follow a link from a mail body filtered by recipient.
   *
   * @code
   * When I follow the link to "user/reset" from the mail to "user@example.com"
   * @endcode
   */
  #[When('I follow the link to :urlFragment from the mail to :to')]
  public function iFollowLinkInMailTo(string $urlFragment, string $to): void {
    $this->followLinkFromMail($urlFragment, $to, '');
  }

  /**
   * Follow a link from an email body filtered by recipient.
   *
   * @code
   * When I follow the link to "user/reset" from the email to "user@example.com"
   * @endcode
   */
  #[When('I follow the link to :urlFragment from the email to :to')]
  public function iFollowLinkInEmailTo(string $urlFragment, string $to): void {
    $this->followLinkFromMail($urlFragment, $to, '');
  }

  /**
   * Follow a link from a mail body filtered by subject.
   *
   * @code
   * When I follow the link to "user/reset" from the mail with the subject "Welcome"
   * @endcode
   */
  #[When('I follow the link to :urlFragment from the mail with the subject :subject')]
  public function iFollowLinkInMailWithSubject(string $urlFragment, string $subject): void {
    $this->followLinkFromMail($urlFragment, '', $subject);
  }

  /**
   * Follow a link from an email body filtered by subject.
   *
   * @code
   * When I follow the link to "user/reset" from the email with the subject "Welcome"
   * @endcode
   */
  #[When('I follow the link to :urlFragment from the email with the subject :subject')]
  public function iFollowLinkInEmailWithSubject(string $urlFragment, string $subject): void {
    $this->followLinkFromMail($urlFragment, '', $subject);
  }

  /**
   * Follow a link from a mail body filtered by recipient and subject.
   *
   * @code
   * When I follow the link to "user/reset" from the mail to "user@example.com" with the subject "Welcome"
   * @endcode
   */
  #[When('I follow the link to :urlFragment from the mail to :to with the subject :subject')]
  public function iFollowLinkInMailToWithSubject(string $urlFragment, string $to, string $subject): void {
    $this->followLinkFromMail($urlFragment, $to, $subject);
  }

  /**
   * Follow a link from an email body filtered by recipient and subject.
   *
   * @code
   * When I follow the link to "user/reset" from the email to "user@example.com" with the subject "Welcome"
   * @endcode
   */
  #[When('I follow the link to :urlFragment from the email to :to with the subject :subject')]
  public function iFollowLinkInEmailToWithSubject(string $urlFragment, string $to, string $subject): void {
    $this->followLinkFromMail($urlFragment, $to, $subject);
  }

  /**
   * Send a mail using the active driver from a TableNode of fields.
   */
  protected function sendMailFromTable(TableNode $fields): void {
    $mail = [
      'body' => $this->getRandom()->name(255),
      'subject' => $this->getRandom()->name(20),
      'to' => $this->getRandom()->name(10) . '@anonexample.com',
      'langcode' => '',
    ];

    foreach ($fields->getRowsHash() as $field => $value) {
      $mail[$field] = is_array($value) ? implode("\n", $value) : (string) $value;
    }

    $driver = $this->getDriver();

    if (!$driver instanceof MailCapabilityInterface) {
      throw new \RuntimeException(sprintf('The active Drupal driver "%s" does not support sending mail.', $driver::class));
    }

    $driver->mailSend($mail['body'], $mail['subject'], $mail['to'], $mail['langcode']);
  }

  /**
   * Compare expected mail rows against all mail captured in the scenario.
   */
  protected function assertMailMatches(TableNode $expectedMailTable, string $to, string $subject): void {
    $expected = $expectedMailTable->getHash();
    $actual = array_values($this->getMail(['to' => $to, 'subject' => $subject]));
    $this->compareMessages($actual, $expected);
  }

  /**
   * Compare expected mail rows against new mail since the last mail check.
   */
  protected function assertNewMailMatches(TableNode $expectedMailTable, string $to, string $subject): void {
    $expected = $expectedMailTable->getHash();
    $actual = array_values($this->getMail(['to' => $to, 'subject' => $subject], TRUE));
    $this->compareMessages($actual, $expected);
  }

  /**
   * Assert the total mail count matches the expected count.
   */
  protected function assertMailCount(string $count, string $to, string $subject): void {
    $actual = array_values($this->getMail(['to' => $to, 'subject' => $subject]));
    $expected_count = match ($count) {
      'no' => 0,
            'a', 'an' => 1,
            default => (int) $count,
    };
    $this->assertMessageCount($actual, $expected_count);
  }

  /**
   * Assert new mail count since the last check matches the expected count.
   */
  protected function assertNewMailCount(string $count, string $to, string $subject): void {
    $actual = array_values($this->getMail(['to' => $to, 'subject' => $subject], TRUE));
    $expected_count = match ($count) {
      'no' => 0,
            'a', 'an' => 1,
            default => (int) $count,
    };
    $this->assertMessageCount($actual, $expected_count);
  }

  /**
   * Visit the first URL in a mail body that matches the given fragment.
   */
  protected function followLinkFromMail(string $urlFragment, string $to, string $subject): void {
    $filters = ['to' => $to, 'subject' => $subject];
    $mail = $this->getMail($filters, FALSE, -1);
    if (count($mail) === 0) {
      throw new ExpectationException('No such mail found.', $this->getSession()->getDriver());
    }
    $body = $mail['body'];

    $pattern = '`.*?((http|https)://[\w#$&+,\/:;=?@.-]+)[^\w#$&+,\/:;=?@.-]*?`i';
    if (preg_match_all($pattern, (string) $body, $urls)) {
      foreach ($urls[1] as $url) {
        if (str_contains(strtolower($url), strtolower($urlFragment))) {
          $this->getMinkContext()->visitPath($url);
          return;
        }
      }

      throw new ExpectationException(sprintf('No URL in mail body contained "%s".', $urlFragment), $this->getSession()->getDriver());
    }

    throw new ExpectationException('No URL found in mail body.', $this->getSession()->getDriver());
  }

}
