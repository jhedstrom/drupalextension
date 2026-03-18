<?php

declare(strict_types=1);

namespace Drupal\DrupalExtension\Context;

use Behat\Hook\BeforeScenario;
use Behat\Hook\AfterScenario;
use Behat\Step\When;
use Behat\Step\Then;
use Behat\Behat\Hook\Scope\ScenarioScope;
use Behat\Gherkin\Node\TableNode;

/**
 * Provides pre-built step definitions for interacting with mail.
 */
class MailContext extends RawMailContext {

  /**
   * By default, prevent mail from being actually sent out during tests.
   */
  #[BeforeScenario]
  public function disableMail(ScenarioScope $event): void {
    if (!$this->hasSendMailTag($event)) {
      $this->getMailManager()->disableMail();
      // Always reset mail count, in case the default mail manager is being used
      // which enables mail collecting automatically when mail is disabled, making
      // the use of the @mail tag optional in this case.
      $this->mailMessageCount = [];
    }
  }

  /**
   * Restore mail sending.
   */
  #[AfterScenario]
  public function enableMail(ScenarioScope $event): void {
    if (!$this->hasSendMailTag($event)) {
      $this->getMailManager()->enableMail();
    }
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
   * This is mainly useful for testing this context.
   *
   * @code
   *   When Drupal sends a mail:
   *     | to      | user@example.com |
   *     | subject | Test mail        |
   *     | body    | Hello world      |
   *   When Drupal sends an email:
   *     | to      | user@example.com |
   *     | subject | Test email       |
   *     | body    | Hello world      |
   * @endcode
   */
  #[When('Drupal sends a/an (e)mail:')]
  public function drupalSendsMail(TableNode $fields): void {
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
   * @code
   *   Then mail has been sent:
   *     | to               | body                |
   *     | user@example.com | Welcome to the site |
   *   Then an email has been sent with the subject "Welcome":
   *     | to               | body                |
   *     | user@example.com | Welcome to the site |
   *   Then emails have been sent to "user@example.com" with the subject "Welcome":
   *     | body                |
   *     | Welcome to the site |
   * @endcode
   */
  #[Then('(a )(an )(e)mail(s) has/have been sent:')]
  #[Then('(a )(an )(e)mail(s) has/have been sent to :to:')]
  #[Then('(a )(an )(e)mail(s) has/have been sent with the subject :subject:')]
  #[Then('(a )(an )(e)mail(s) has/have been sent to :to with the subject :subject:')]
  public function mailHasBeenSent(TableNode $expectedMailTable, string $to = '', string $subject = ''): void {
    $expected = $expectedMailTable->getHash();
    $actual = $this->getMail(['to' => $to, 'subject' => $subject]);
    $this->compareMessages($actual, $expected);
  }

  /**
   * Check mail sent since the last step that checked mail.
   *
   * @code
   *   Then new mail is sent:
   *     | subject   |
   *     | Greetings |
   *   Then a new email is sent to "user@example.com":
   *     | subject   |
   *     | Greetings |
   * @endcode
   */
  #[Then('(a )(an )new (e)mail(s) is/are sent:')]
  #[Then('(a )(an )new (e)mail(s) is/are sent to :to:')]
  #[Then('(a )(an )new (e)mail(s) is/are sent with the subject :subject:')]
  #[Then('(a )(an )new (e)mail(s) is/are sent to :to with the subject :subject:')]
  public function newMailIsSent(TableNode $expectedMailTable, string $to = '', string $subject = ''): void {
    $expected = $expectedMailTable->getHash();
    $actual = $this->getMail(['to' => $to, 'subject' => $subject], TRUE);
    $this->compareMessages($actual, $expected);
  }

  /**
   * Check all mail sent during the scenario.
   *
   * @code
   * Then 0 emails have been sent
   * Then 2 mails have been sent to "user@example.com"
   * Then 1 email has been sent with the subject "Welcome"
   * @endcode
   */
  #[Then(':count (e)mail(s) has/have been sent')]
  #[Then(':count (e)mail(s) has/have been sent to :to')]
  #[Then(':count (e)mail(s) has/have been sent with the subject :subject')]
  #[Then(':count (e)mail(s) has/have been sent to :to with the subject :subject')]
  public function noMailHasBeenSent(string $count, string $to = '', string $subject = ''): void {
    $actual = $this->getMail(['to' => $to, 'subject' => $subject]);
    $expectedCount = match ($count) {
      'no' => 0,
            'a', 'an' => NULL,
            default => (int) $count,
    };
    $this->assertMessageCount($actual, $expectedCount);
  }

  /**
   * Check mail sent since the last step that checked mail.
   *
   * @code
   * Then 0 new emails are sent
   * Then 1 new mail is sent to "user@example.com"
   * @endcode
   */
  #[Then(':count new (e)mail(s) is/are sent')]
  #[Then(':count new (e)mail(s) is/are sent to :to')]
  #[Then(':count new (e)mail(s) is/are sent with the subject :subject')]
  #[Then(':count new (e)mail(s) is/are sent to :to with the subject :subject')]
  public function noNewMailIsSent(string $count, string $to = '', string $subject = ''): void {
    $actual = $this->getMail(['to' => $to, 'subject' => $subject], TRUE);
    $expectedCount = match ($count) {
      'no' => 0,
            'a', 'an' => 1,
            default => (int) $count,
    };
    $this->assertMessageCount($actual, $expectedCount);
  }

  /**
   * Follow a link from an email body.
   *
   * @code
   * When I follow the link to "user/reset" from the mail
   * When I follow the link to "user/reset" from the email
   * When I follow the link to "user/reset" from the email to "user@example.com"
   * When I follow the link to "user/reset" from the email with the subject "Welcome"
   * @endcode
   */
  #[When('I follow the link to :urlFragment from the (e)mail')]
  #[When('I follow the link to :urlFragment from the (e)mail to :to')]
  #[When('I follow the link to :urlFragment from the (e)mail with the subject :subject')]
  #[When('I follow the link to :urlFragment from the (e)mail to :to with the subject :subject')]
  public function followLinkInMail(string $urlFragment, string $to = '', string $subject = ''): void {
    // Get the message.
    $filters = ['to' => $to, 'subject' => $subject];
    $mail = $this->getMail($filters, FALSE, -1);
    if (count($mail) === 0) {
      throw new \Exception('No such mail found.');
    }
    $body = $mail['body'];

    // Find web URLs in the message.
    $pattern = '`.*?((http|https)://[\w#$&+,\/:;=?@.-]+)[^\w#$&+,\/:;=?@.-]*?`i';
    if (preg_match_all($pattern, (string) $body, $urls)) {
      // Visit the first url that contains the desired fragment.
      foreach ($urls[1] as $url) {
        if (str_contains(strtolower($url), strtolower($urlFragment))) {
          $this->getMinkContext()->visitPath($url);
          return;
        }
      }

      throw new \Exception(sprintf('No URL in mail body contained "%s".', $urlFragment));
    }

    throw new \Exception('No URL found in mail body.');
  }

}
