<?php

namespace Drupal\DrupalExtension\Context;

use Behat\Behat\Context\TranslatableContext;
use Behat\Gherkin\Node\TableNode;
use Behat\Mink\Exception\ExpectationException;

/**
 * Provides step-definitions for interacting with Drupal messages.
 */
class MessageContext extends RawDrupalContext implements TranslatableContext {

  /**
   * {@inheritDoc}
   */
  public static function getTranslationResources() {
    return glob(__DIR__ . '/../../../../i18n/*.xliff');
  }

  /**
   * Checks if the current page contains the given error message
   *
   * @param $message
   *   string The text to be checked
   *
   * @Then I should see the error message( containing) :message
   */
  public function assertErrorVisible($message) {
    $this->_assert(
      $message,
      'error_message_selector',
      "The page '%s' does not contain any error messages",
      "The page '%s' does not contain the error message '%s'"
    );
  }

  /**
   * Checks if the current page contains the given set of error messages
   *
   * @param $messages
   *   array An array of texts to be checked
   *
   * @Then I should see the following error message(s):
   */
  public function assertMultipleErrors(TableNode $messages) {
    foreach ($messages->getHash() as $key => $value) {
      $message = trim($value['error messages']);
      $this->assertErrorVisible($message);
    }
  }

  /**
   * Checks if the current page does not contain the given error message
   *
   * @param $message
   *   string The text to be checked
   *
   * @Given I should not see the error message( containing) :message
   */
  public function assertNotErrorVisible($message) {
    $this->_assertNot(
      $message,
      'error_message_selector',
      "The page '%s' contains the error message '%s'"
    );
  }

  /**
   * Checks if the current page does not contain the given set error messages
   *
   * @param $messages
   *   array An array of texts to be checked
   *
   * @Then I should not see the following error messages:
   */
  public function assertNotMultipleErrors(TableNode $messages) {
    foreach ($messages->getHash() as $key => $value) {
      $message = trim($value['error messages']);
      $this->assertNotErrorVisible($message);
    }
  }

  /**
   * Checks if the current page contains the given success message
   *
   * @param $message
   *   string The text to be checked
   *
   * @Then I should see the success message( containing) :message
   */
  public function assertSuccessMessage($message) {
    $this->_assert(
      $message,
      'success_message_selector',
      "The page '%s' does not contain any success messages",
      "The page '%s' does not contain the success message '%s'"
    );
  }

  /**
   * Checks if the current page contains the given set of success messages
   *
   * @param $message
   *   array An array of texts to be checked
   *
   * @Then I should see the following success messages:
   */
  public function assertMultipleSuccessMessage(TableNode $messages) {
    foreach ($messages->getHash() as $key => $value) {
      $message = trim($value['success messages']);
      $this->assertSuccessMessage($message);
    }
  }

  /**
   * Checks if the current page does not contain the given set of success message
   *
   * @param $message
   *   string The text to be checked
   *
   * @Given I should not see the success message( containing) :message
   */
  public function assertNotSuccessMessage($message) {
    $this->_assertNot(
      $message,
      'success_message_selector',
      "The page '%s' contains the success message '%s'"
    );
  }

  /**
   * Checks if the current page does not contain the given set of success messages
   *
   * @param $message
   *   array An array of texts to be checked
   *
   * @Then I should not see the following success messages:
   */
  public function assertNotMultipleSuccessMessage(TableNode $messages) {
    foreach ($messages->getHash() as $key => $value) {
      $message = trim($value['success messages']);
      $this->assertNotSuccessMessage($message);
    }
  }

  /**
   * Checks if the current page contains the given warning message
   *
   * @param $message
   *   string The text to be checked
   *
   * @Then I should see the warning message( containing) :message
   */
  public function assertWarningMessage($message) {
    $this->_assert(
      $message,
      'warning_message_selector',
      "The page '%s' does not contain any warning messages",
      "The page '%s' does not contain the warning message '%s'"
    );
  }

  /**
   * Checks if the current page contains the given set of warning messages
   *
   * @param $message
   *   array An array of texts to be checked
   *
   * @Then I should see the following warning messages:
   */
  public function assertMultipleWarningMessage(TableNode $messages) {
    foreach ($messages->getHash() as $key => $value) {
      $message = trim($value['warning messages']);
      $this->assertWarningMessage($message);
    }
  }

  /**
   * Checks if the current page does not contain the given set of warning message
   *
   * @param $message
   *   string The text to be checked
   *
   * @Given I should not see the warning message( containing) :message
   */
  public function assertNotWarningMessage($message) {
    $this->_assertNot(
      $message,
      'warning_message_selector',
      "The page '%s' contains the warning message '%s'"
    );
  }

  /**
   * Checks if the current page does not contain the given set of warning messages
   *
   * @param $message
   *   array An array of texts to be checked
   *
   * @Then I should not see the following warning messages:
   */
  public function assertNotMultipleWarningMessage(TableNode $messages) {
    foreach ($messages->getHash() as $key => $value) {
      $message = trim($value['warning messages']);
      $this->assertNotWarningMessage($message);
    }
  }

  /**
   * Checks if the current page contain the given message
   *
   * @param $message
   *   string The message to be checked
   *
   * @Then I should see the message( containing) :message
   */
  public function assertMessage($message) {
    $this->_assert(
      $message,
      'message_selector',
      "The page '%s' does not contain any messages",
      "The page '%s' does not contain the message '%s'"
    );
  }

  /**
   * Checks if the current page does not contain the given message
   *
   * @param $message
   *   string The message to be checked
   *
   * @Then I should not see the message( containing) :message
   */
  public function assertNotMessage($message) {
    $this->_assertNot(
      $message,
      'message_selector',
      "The page '%s' contains the message '%s'"
    );
  }

  /**
   * Internal callback to check for a specific message in a given context.
   *
   * @param $message
   *   string The message to be checked
   * @param $selectorId
   *   string CSS selector name
   * @param $exceptionMsgNone
   *   string The message being thrown when no message is contained, string
   *   should contain one '%s' as a placeholder for the current URL
   * @param $exceptionMsgMissing
   *   string The message being thrown when the message is not contained, string
   *   should contain two '%s' as placeholders for the current URL and the message.
   *
   * @throws \Behat\Mink\Exception\ExpectationException
   *   Thrown when the expected message is not present in the page.
   */
  private function _assert($message, $selectorId, $exceptionMsgNone, $exceptionMsgMissing) {
    $selector = $this->getDrupalSelector($selectorId);
    $selectorObjects = $this->getSession()->getPage()->findAll("css", $selector);
    if (empty($selectorObjects)) {
      throw new ExpectationException(sprintf($exceptionMsgNone, $this->getSession()->getCurrentUrl()), $this->getSession()->getDriver());
    }
    foreach ($selectorObjects as $selectorObject) {
      if (strpos(trim($selectorObject->getText()), $message) !== FALSE) {
        return;
      }
    }
    throw new ExpectationException(sprintf($exceptionMsgMissing, $this->getSession()->getCurrentUrl(), $message), $this->getSession()->getDriver());
  }

  /**
   * Internal callback to check if the current page does not contain the given message
   *
   * @param $message
   *   string The message to be checked
   * @param $selectorId
   *   string CSS selector name
   * @param $exceptionMsg
   *   string The message being thrown when the message is contained, string
   *   should contain two '%s' as placeholders for the current URL and the message.
   *
   * @throws \Behat\Mink\Exception\ExpectationException
   *   Thrown when the expected message is present in the page.
   */
  private function _assertNot($message, $selectorId, $exceptionMsg) {
    $selector = $this->getDrupalSelector($selectorId);
    $selectorObjects = $this->getSession()->getPage()->findAll("css", $selector);
    if (!empty($selectorObjects)) {
      foreach ($selectorObjects as $selectorObject) {
        if (strpos(trim($selectorObject->getText()), $message) !== FALSE) {
          throw new ExpectationException(sprintf($exceptionMsg, $this->getSession()->getCurrentUrl(), $message), $this->getSession()->getDriver());
        }
      }
    }
  }

}
