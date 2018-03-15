<?php

namespace Drupal;

/**
 * Interface for classes that manage mail created during tests.
 */
interface DrupalMailManagerInterface
{

  /**
   * Collect outbound mail for analysis.
   */
    public function startCollectingMail();

  /**
   * Stop collecting outbound mail.
   */
    public function stopCollectingMail();
  
  /**
   * Allow mail to be actually sent out.
   */
    public function enableMail();

  /**
   * Prevent mail from being actually sent out.
   */
    public function disableMail();

  /**
   * Get all collected mail.
   *
   * @param string $store
   *   The name of the mail store to get mail from.
   *
   * @return \stdClass[]
   *   An array of collected emails, each formatted as a Drupal 8
   * \Drupal\Core\Mail\MailInterface::mail $message array.
   */
    public function getMail($store);

  /**
   * Empty the store of collected mail.
   */
    public function clearMail($store);
}
