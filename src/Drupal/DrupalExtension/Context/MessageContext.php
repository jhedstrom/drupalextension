<?php

declare(strict_types=1);

namespace Drupal\DrupalExtension\Context;

use Behat\Behat\Context\TranslatableContext;
use Behat\Gherkin\Node\TableNode;
use Behat\Mink\Exception\ExpectationException;
use Behat\MinkExtension\Context\RawMinkContext;
use Behat\Step\Given;
use Behat\Step\Then;
use Drupal\DrupalExtension\DrupalParametersTrait;

/**
 * Provides step-definitions for interacting with Drupal messages.
 *
 * Operates against the rendered page via Mink and four CSS selectors. The
 * selectors are read from the 'selectors:' map under 'Drupal\MinkExtension'
 * (preferred) and fall back to the legacy 'selectors:' map under
 * 'Drupal\DrupalExtension' (deprecated, removed in 6.1).
 */
class MessageContext extends RawMinkContext implements TranslatableContext, DrupalParametersAwareInterface {

  use DrupalParametersTrait;

  /**
   * {@inheritdoc}
   */
  public static function getTranslationResources() {
    return glob(__DIR__ . '/../../../../i18n/*.xliff') ?: [];
  }

  /**
   * Checks if the current page contains the given error message.
   *
   * @param string $message
   *   The text to be checked.
   *
   * @code
   *   Then I should see the error message "Username is required"
   *   Then I should see the error message containing "Username"
   * @endcode
   */
  #[Then('I should see the error message( containing) :message')]
  public function errorMessageAssertIsVisible(string $message): void {
    $this->assert(
          $message,
          'error_message_selector',
          "The page '%s' does not contain any error messages",
          "The page '%s' does not contain the error message '%s'"
      );
  }

  /**
   * Checks if the current page contains the given set of error messages.
   *
   * @param \Behat\Gherkin\Node\TableNode $messages
   *   An array of texts to be checked. The first row should consist of the
   *   string "Error messages".
   *
   * @code
   *   Then I should see the following error messages:
   *     | error messages         |
   *     | Username is required   |
   *     | Password is required   |
   * @endcode
   */
  #[Then('I should see the following error message(s):')]
  public function errorMessagesAssertAreVisible(TableNode $messages): void {
    $this->assertValidMessageTable($messages, 'error messages');
    foreach ($messages->getHash() as $value) {
      $value = array_change_key_case($value);
      $message = trim($value['error messages']);
      $this->errorMessageAssertIsVisible($message);
    }
  }

  /**
   * Checks if the current page does not contain the given error message.
   *
   * @param string $message
   *   The text to be checked.
   *
   * @code
   *   Given I should not see the error message "Access denied"
   *   Given I should not see the error message containing "Access"
   * @endcode
   */
  #[Given('I should not see the error message( containing) :message')]
  public function assertNotErrorVisible(string $message): void {
    $this->assertNot(
          $message,
          'error_message_selector',
          "The page '%s' contains the error message '%s'"
      );
  }

  /**
   * Checks if the current page does not contain the given set error messages.
   *
   * @param \Behat\Gherkin\Node\TableNode $messages
   *   An array of texts to be checked. The first row should consist of the
   *   string "Error messages".
   *
   * @code
   *   Then I should not see the following error messages:
   *     | error messages |
   *     | Access denied  |
   * @endcode
   */
  #[Then('I should not see the following error messages:')]
  public function errorMessagesAssertAreNotVisible(TableNode $messages): void {
    $this->assertValidMessageTable($messages, 'error messages');
    foreach ($messages->getHash() as $value) {
      $value = array_change_key_case($value);
      $message = trim($value['error messages']);
      $this->assertNotErrorVisible($message);
    }
  }

  /**
   * Checks if the current page contains the given success message.
   *
   * @param string $message
   *   The text to be checked.
   *
   * @code
   *   Then I should see the success message "Article has been created"
   *   Then I should see the success message containing "created"
   * @endcode
   */
  #[Then('I should see the success message( containing) :message')]
  public function successMessageAssertIsVisible(string $message): void {
    $this->assert(
          $message,
          'success_message_selector',
          "The page '%s' does not contain any success messages",
          "The page '%s' does not contain the success message '%s'"
      );
  }

  /**
   * Checks if the current page contains the given set of success messages.
   *
   * @param \Behat\Gherkin\Node\TableNode $messages
   *   An array of texts to be checked. The first row should consist of the
   *   string "Success messages".
   *
   * @code
   *   Then I should see the following success messages:
   *     | success messages        |
   *     | Article has been created |
   * @endcode
   */
  #[Then('I should see the following success messages:')]
  public function successMessagesAssertAreVisible(TableNode $messages): void {
    $this->assertValidMessageTable($messages, 'success messages');
    foreach ($messages->getHash() as $value) {
      $value = array_change_key_case($value);
      $message = trim($value['success messages']);
      $this->successMessageAssertIsVisible($message);
    }
  }

  /**
   * Checks the page does not contain the given success message.
   *
   * @param string $message
   *   The text to be checked.
   *
   * @code
   *   Given I should not see the success message "saved"
   *   Given I should not see the success message containing "saved"
   * @endcode
   */
  #[Given('I should not see the success message( containing) :message')]
  public function assertNotSuccessMessage(string $message): void {
    $this->assertNot(
          $message,
          'success_message_selector',
          "The page '%s' contains the success message '%s'"
      );
  }

  /**
   * Checks the page does not contain the given set of success messages.
   *
   * @param \Behat\Gherkin\Node\TableNode $messages
   *   An array of texts to be checked. The first row should consist of the
   *   string "Success messages".
   *
   * @code
   *   Then I should not see the following success messages:
   *     | success messages |
   *     | Changes saved    |
   * @endcode
   */
  #[Then('I should not see the following success messages:')]
  public function successMessagesAssertAreNotVisible(TableNode $messages): void {
    $this->assertValidMessageTable($messages, 'success messages');
    foreach ($messages->getHash() as $value) {
      $value = array_change_key_case($value);
      $message = trim($value['success messages']);
      $this->assertNotSuccessMessage($message);
    }
  }

  /**
   * Checks if the current page contains the given warning message.
   *
   * @param string $message
   *   The text to be checked.
   *
   * @code
   *   Then I should see the warning message "This action cannot be undone"
   *   Then I should see the warning message containing "cannot be undone"
   * @endcode
   */
  #[Then('I should see the warning message( containing) :message')]
  public function warningMessageAssertIsVisible(string $message): void {
    $this->assert(
          $message,
          'warning_message_selector',
          "The page '%s' does not contain any warning messages",
          "The page '%s' does not contain the warning message '%s'"
      );
  }

  /**
   * Checks if the current page contains the given set of warning messages.
   *
   * @param \Behat\Gherkin\Node\TableNode $messages
   *   An array of texts to be checked. The first row should consist of the
   *   string "Warning messages".
   *
   * @code
   *   Then I should see the following warning messages:
   *     | warning messages                |
   *     | This action cannot be undone    |
   * @endcode
   */
  #[Then('I should see the following warning message(s):')]
  public function warningMessagesAssertAreVisible(TableNode $messages): void {
    $this->assertValidMessageTable($messages, 'warning messages');
    foreach ($messages->getHash() as $value) {
      $value = array_change_key_case($value);
      $message = trim($value['warning messages']);
      $this->warningMessageAssertIsVisible($message);
    }
  }

  /**
   * Checks the page does not contain the given warning message.
   *
   * @param string $message
   *   The text to be checked.
   *
   * @code
   *   Given I should not see the warning message "deprecated"
   *   Given I should not see the warning message containing "deprecated"
   * @endcode
   */
  #[Given('I should not see the warning message( containing) :message')]
  public function assertNotWarningMessage(string $message): void {
    $this->assertNot(
          $message,
          'warning_message_selector',
          "The page '%s' contains the warning message '%s'"
      );
  }

  /**
   * Checks the page does not contain the given set of warning messages.
   *
   * @param \Behat\Gherkin\Node\TableNode $messages
   *   An array of texts to be checked. The first row should consist of the
   *   string "Warning messages".
   *
   * @code
   *   Then I should not see the following warning messages:
   *     | warning messages |
   *     | deprecated       |
   * @endcode
   */
  #[Then('I should not see the following warning messages:')]
  public function warningMessagesAssertAreNotVisible(TableNode $messages): void {
    $this->assertValidMessageTable($messages, 'warning messages');
    foreach ($messages->getHash() as $value) {
      $value = array_change_key_case($value);
      $message = trim($value['warning messages']);
      $this->assertNotWarningMessage($message);
    }
  }

  /**
   * Checks if the current page contain the given message.
   *
   * @param string $message
   *   The message to be checked.
   *
   * @code
   *   Then I should see the message "Changes saved"
   *   Then I should see the message containing "saved"
   * @endcode
   */
  #[Then('I should see the message( containing) :message')]
  public function messageAssertIsVisible(string $message): void {
    $this->assert(
          $message,
          'message_selector',
          "The page '%s' does not contain any messages",
          "The page '%s' does not contain the message '%s'"
      );
  }

  /**
   * Checks if the current page does not contain the given message.
   *
   * @param string $message
   *   The message to be checked.
   *
   * @code
   *   Then I should not see the message "Access denied"
   *   Then I should not see the message containing "denied"
   * @endcode
   */
  #[Then('I should not see the message( containing) :message')]
  public function messageAssertIsNotVisible(string $message): void {
    $this->assertNot(
          $message,
          'message_selector',
          "The page '%s' contains the message '%s'"
      );
  }

  /**
   * Checks whether the given list of messages is valid.
   *
   * This checks whether the list has only one column and has the correct
   * header.
   *
   * @param \Behat\Gherkin\Node\TableNode $messages
   *   The list of messages.
   * @param string $expected_header
   *   The header that should be present in the list.
   */
  protected function assertValidMessageTable(TableNode $messages, string $expected_header): void {
    // Check that the table only contains a single column.
    $header_row = $messages->getRow(0);

    $column_count = count($header_row);
    if ($column_count !== 1) {
      throw new \RuntimeException(sprintf('The list of %s should only contain 1 column. It has %s columns.', $expected_header, $column_count));
    }

    // Check that the correct header is used.
    $actual_header = reset($header_row);
    if (strtolower(trim((string) $actual_header)) !== $expected_header) {
      $capitalized_header = ucfirst($expected_header);
      throw new \RuntimeException(sprintf("The list of %s should have the header '%s', but found '%s'.", $expected_header, $capitalized_header, $actual_header));
    }
  }

  /**
   * Resolves a message selector by name.
   *
   * Reads from the 'selectors:' map under 'Drupal\MinkExtension' first.
   * When no value is set there, falls back to the legacy 'selectors:' map
   * under 'Drupal\DrupalExtension' via 'getDrupalSelector()' and emits a
   * one-shot deprecation notice.
   *
   * @param string $name
   *   One of 'message_selector', 'error_message_selector',
   *   'success_message_selector', 'warning_message_selector'.
   *
   * @return string
   *   The resolved CSS selector.
   *
   * @throws \RuntimeException
   *   When the selector is not configured under either extension.
   */
  protected function getMessageSelector(string $name): string {
    $mink_selectors = $this->getMinkParameter('selectors');

    if (is_array($mink_selectors) && isset($mink_selectors[$name])) {
      return $mink_selectors[$name];
    }

    static $deprecation_emitted = FALSE;

    if (!$deprecation_emitted) {
      // Match the legacy field-parser deprecation pattern: write to STDERR
      // directly so Behat does not escalate the notice to a step failure.
      fwrite(STDERR, '[Deprecation] Configuring message selectors under "Drupal\DrupalExtension.selectors:" is deprecated and will be removed in 6.1. Move the four message selectors (message_selector, error_message_selector, success_message_selector, warning_message_selector) to "Drupal\MinkExtension.selectors:" in your behat.yml. See MIGRATION.md.' . PHP_EOL);
      $deprecation_emitted = TRUE;
    }

    return $this->getDrupalSelector($name);
  }

  /**
   * Internal callback to check for a specific message in a given context.
   *
   * @param string $message
   *   The message to be checked.
   * @param string $selectorId
   *   CSS selector name.
   * @param string $exceptionMsgNone
   *   The message being thrown when no message is contained, string should
   *   contain one '%s' as a placeholder for the current URL.
   * @param string $exceptionMsgMissing
   *   The message being thrown when the message is not contained, string should
   *   contain two '%s' as placeholders for the current URL and the message.
   *
   * @throws \Behat\Mink\Exception\ExpectationException
   *   Thrown when the expected message is not present in the page.
   */
  protected function assert(string $message, string $selectorId, string $exceptionMsgNone, string $exceptionMsgMissing): void {
    $selector = $this->getMessageSelector($selectorId);
    $selector_objects = $this->getSession()->getPage()->findAll("css", $selector);
    if (empty($selector_objects)) {
      throw new ExpectationException(sprintf($exceptionMsgNone, $this->getSession()->getCurrentUrl()), $this->getSession()->getDriver());
    }
    foreach ($selector_objects as $selector_object) {
      if (str_contains(trim($selector_object->getText()), $message)) {
        return;
      }
    }
    throw new ExpectationException(sprintf($exceptionMsgMissing, $this->getSession()->getCurrentUrl(), $message), $this->getSession()->getDriver());
  }

  /**
   * Internal callback to check the page does not contain the given message.
   *
   * @param string $message
   *   The message to be checked.
   * @param string $selectorId
   *   CSS selector name.
   * @param string $exceptionMsg
   *   The message being thrown when the message is contained, string should
   *   contain two '%s' as placeholders for the current URL and the message.
   *
   * @throws \Behat\Mink\Exception\ExpectationException
   *   Thrown when the expected message is present in the page.
   */
  protected function assertNot(string $message, string $selectorId, string $exceptionMsg): void {
    $selector = $this->getMessageSelector($selectorId);
    $selector_objects = $this->getSession()->getPage()->findAll("css", $selector);
    if (!empty($selector_objects)) {
      foreach ($selector_objects as $selector_object) {
        if (str_contains(trim($selector_object->getText()), $message)) {
          throw new ExpectationException(sprintf($exceptionMsg, $this->getSession()->getCurrentUrl(), $message), $this->getSession()->getDriver());
        }
      }
    }
  }

}
