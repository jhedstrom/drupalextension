<?php

declare(strict_types=1);

namespace Drupal\DrupalExtension\Manager;

/**
 * Interface for classes that manage mail created during tests.
 */
interface DrupalMailManagerInterface {

  /**
   * Collect outbound mail for analysis.
   */
  public function startCollectingMail(): void;

  /**
   * Stop collecting outbound mail.
   */
  public function stopCollectingMail(): void;

  /**
   * Allow mail to be actually sent out.
   */
  public function enableMail(): void;

  /**
   * Prevent mail from being actually sent out.
   */
  public function disableMail(): void;

  /**
   * Get all collected mail.
   *
   * @return array<int, array<string, mixed>>
   *   An array of collected emails. Each item is a Drupal mail message
   *   array as produced by 'MailInterface::mail()' - the keys include
   *   'to', 'subject', 'body', 'headers', etc.
   */
  public function getMail(): array;

  /**
   * Empty the store of collected mail.
   */
  public function clearMail(): void;

}
