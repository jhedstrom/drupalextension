<?php

namespace Drupal\DrupalExtension\Context;

use Behat\Gherkin\Node\TableNode;

/**
 * Provides pre-built step definitions for interacting with mail.
 */
class MailContext extends RawMailContext
{

    /**
   * By default, prevent mail from being actually sent out during tests.
   *
   * @BeforeScenario
   */
    public function disableMail($event)
    {
        $tags = array_merge($event->getFeature()->getTags(), $event->getScenario()->getTags());
        if (!in_array('sendmail', $tags) && !in_array('sendemail', $tags)) {
            $this->getMailManager()->disableMail();
            // Always reset mail count, in case the default mail manager is being used
            // which enables mail collecting automatically when mail is disabled, making
            //the use of the @mail tag optional in this case.
            $this->mailCount = [];
        }
    }

  /**
   * Restore mail sending.
   *
   * @AfterScenario
   */
    public function enableMail($event)
    {
        $tags = array_merge($event->getFeature()->getTags(), $event->getScenario()->getTags());
        if (!in_array('sendmail', $tags) && !in_array('sendemail', $tags)) {
            $this->getMailManager()->enableMail();
        }
    }

  /**
   * Allow opting in to mail collection. When using the default mail manager
   * service, it is not necessary to use this tag.
   *
   * @BeforeScenario @mail @email
   */
    public function collectMail()
    {
        $this->getMailManager()->startCollectingMail();
    }

  /**
   * Stop collecting mail at scenario end.
   *
   * @AfterScenario @mail @email
   */
    public function stopCollectingMail()
    {
        $this->getMailManager()->stopCollectingMail();
    }

  /**
   * This is mainly useful for testing this context.
   *
   * @When Drupal sends a/an (e)mail:
   */
    public function drupalSendsMail(TableNode $fields)
    {
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
   * @Then (a )(an )(e)mail(s) has/have been sent:
   * @Then (a )(an )(e)mail(s) has/have been sent to :to:
   * @Then (a )(an )(e)mail(s) has/have been sent with the subject :subject:
   * @Then (a )(an )(e)mail(s) has/have been sent to :to with the subject :subject:
   */
    public function mailHasBeenSent(TableNode $expectedMailTable, $to = '', $subject = '')
    {
        $expectedMail = $expectedMailTable->getHash();
        $actualMail = $this->getMail(['to' => $to, 'subject' => $subject], false);
        $this->compareMail($actualMail, $expectedMail);
    }

  /**
   * Check mail sent since the last step that checked mail.
   *
   * @Then (a )(an )new (e)mail(s) is/are sent:
   * @Then (a )(an )new (e)mail(s) is/are sent to :to:
   * @Then (a )(an )new (e)mail(s) is/are sent with the subject :subject:
   * @Then (a )(an )new (e)mail(s) is/are sent to :to with the subject :subject:
   */
    public function newMailIsSent(TableNode $expectedMailTable, $to = '', $subject = '')
    {
        $expectedMail = $expectedMailTable->getHash();
        $actualMail = $this->getMail(['to' => $to, 'subject' => $subject], true);
        $this->compareMail($actualMail, $expectedMail);
    }

  /**
   * Check all mail sent during the scenario.
   *
   * @Then :count (e)mail(s) has/have been sent
   * @Then :count (e)mail(s) has/have been sent to :to
   * @Then :count (e)mail(s) has/have been sent with the subject :subject
   * @Then :count (e)mail(s) has/have been sent to :to with the subject :subject
   */
    public function noMailHasBeenSent($count, $to = '', $subject = '')
    {
        $actualMail = $this->getMail(['to' => $to, 'subject' => $subject], false);
        $count = $count === 'no' ? 0 : $count;
        $count = $count === 'a' ? null : $count;
        $count = $count === 'an' ? null : $count;
        $this->assertMailCount($actualMail, $count);
    }

  /**
   * Check mail sent since the last step that checked mail.
   *
   * @Then :count new (e)mail(s) is/are sent
   * @Then :count new (e)mail(s) is/are sent to :to
   * @Then :count new (e)mail(s) is/are sent with the subject :subject
   * @Then :count new (e)mail(s) is/are sent to :to with the subject :subject
   */
    public function noNewMailIsSent($count, $to = '', $subject = '')
    {
        $actualMail = $this->getMail(['to' => $to, 'subject' => $subject], true);
        $count = $count === 'no' ? 0 : $count;
        $count = $count === 'a' ? 1 : $count;
        $count = $count === 'an' ? 1 : $count;
        $this->assertMailCount($actualMail, $count);
    }
  
  /**
   * @When I follow the link to :urlFragment from the (e)mail
   * @When I follow the link to :urlFragment from the (e)mail to :to
   * @When I follow the link to :urlFragment from the (e)mail with the subject :subject
   * @When I follow the link to :urlFragment from the (e)mail to :to with the subject :subject
   */
    public function followLinkInMail($urlFragment, $to = '', $subject = '')
    {
        // Get the mail
        $matches = ['to' => $to, 'subject' => $subject];
        $mail = $this->getMail($matches, false, -1);
        if (count($mail) == 0) {
            throw new \Exception('No such mail found.');
        }
        $body = $mail['body'];

        // Find web URLs in the mail
        $urlPattern = '`.*?((http|https)://[\w#$&+,\/:;=?@.-]+)[^\w#$&+,\/:;=?@.-]*?`i';
        if (preg_match_all($urlPattern, $body, $urls)) {
            // Visit the first url that contains the desired fragment.
            foreach ($urls[1] as $url) {
                $match = (strpos(strtolower($url), strtolower($urlFragment)) !== false);
                if ($match) {
                    $this->getMinkContext()->visitPath($url);
                    return;
                }
            }
            throw new \Exception(sprintf('No URL in mail body contained "%s".', $urlFragment));
        } else {
            throw new \Exception('No URL found in mail body.');
        }
    }
}
