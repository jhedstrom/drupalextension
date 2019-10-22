<?php

namespace Drupal\DrupalExtension\Context;

use Behat\Behat\Context\TranslatableContext;
use Behat\Gherkin\Node\TableNode;
use Behat\Mink\Exception\ExpectationException;

/**
 * Provides step-definitions for interacting with Drupal messages.
 */
class MessageContext extends RawDrupalContext implements TranslatableContext
{

  /**
   * {@inheritDoc}
   */
    public static function getTranslationResources()
    {
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
    public function assertErrorVisible($message)
    {
        $this->assert(
            $message,
            'error_message_selector',
            "The page '%s' does not contain any error messages",
            "The page '%s' does not contain the error message '%s'"
        );
    }

  /**
   * Checks if the current page contains the given set of error messages
   *
   * @param array $messages
   *   An array of texts to be checked. The first row should consist of the
   *   string "Error messages".
   *
   * @Then I should see the following error message(s):
   */
    public function assertMultipleErrors(TableNode $messages)
    {
        $this->assertValidMessageTable($messages, 'error messages');
        foreach ($messages->getHash() as $key => $value) {
            $value = array_change_key_case($value);
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
    public function assertNotErrorVisible($message)
    {
        $this->assertNot(
            $message,
            'error_message_selector',
            "The page '%s' contains the error message '%s'"
        );
    }

  /**
   * Checks if the current page does not contain the given set error messages
   *
   * @param array $messages
   *   An array of texts to be checked. The first row should consist of the
   *   string "Error messages".
   *
   * @Then I should not see the following error messages:
   */
    public function assertNotMultipleErrors(TableNode $messages)
    {
        $this->assertValidMessageTable($messages, 'error messages');
        foreach ($messages->getHash() as $key => $value) {
            $value = array_change_key_case($value);
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
    public function assertSuccessMessage($message)
    {
        $this->assert(
            $message,
            'success_message_selector',
            "The page '%s' does not contain any success messages",
            "The page '%s' does not contain the success message '%s'"
        );
    }

  /**
   * Checks if the current page contains the given set of success messages
   *
   * @param array $message
   *   An array of texts to be checked. The first row should consist of the
   *   string "Success messages".
   *
   * @Then I should see the following success messages:
   */
    public function assertMultipleSuccessMessage(TableNode $messages)
    {
        $this->assertValidMessageTable($messages, 'success messages');
        foreach ($messages->getHash() as $key => $value) {
            $value = array_change_key_case($value);
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
    public function assertNotSuccessMessage($message)
    {
        $this->assertNot(
            $message,
            'success_message_selector',
            "The page '%s' contains the success message '%s'"
        );
    }

  /**
   * Checks if the current page does not contain the given set of success messages
   *
   * @param array $message
   *   An array of texts to be checked. The first row should consist of the
   *   string "Success messages".
   *
   * @Then I should not see the following success messages:
   */
    public function assertNotMultipleSuccessMessage(TableNode $messages)
    {
        $this->assertValidMessageTable($messages, 'success messages');
        foreach ($messages->getHash() as $key => $value) {
            $value = array_change_key_case($value);
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
    public function assertWarningMessage($message)
    {
        $this->assert(
            $message,
            'warning_message_selector',
            "The page '%s' does not contain any warning messages",
            "The page '%s' does not contain the warning message '%s'"
        );
    }

  /**
   * Checks if the current page contains the given set of warning messages
   *
   * @param array $message
   *   An array of texts to be checked. The first row should consist of the
   *   string "Warning messages".
   *
   * @Then I should see the following warning messages:
   */
    public function assertMultipleWarningMessage(TableNode $messages)
    {
        $this->assertValidMessageTable($messages, 'warning messages');
        foreach ($messages->getHash() as $key => $value) {
            $value = array_change_key_case($value);
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
    public function assertNotWarningMessage($message)
    {
        $this->assertNot(
            $message,
            'warning_message_selector',
            "The page '%s' contains the warning message '%s'"
        );
    }

  /**
   * Checks if the current page does not contain the given set of warning messages
   *
   * @param array $message
   *   An array of texts to be checked. The first row should consist of the
   *   string "Warning messages".
   *
   * @Then I should not see the following warning messages:
   */
    public function assertNotMultipleWarningMessage(TableNode $messages)
    {
        $this->assertValidMessageTable($messages, 'warning messages');
        foreach ($messages->getHash() as $key => $value) {
            $value = array_change_key_case($value);
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
    public function assertMessage($message)
    {
        $this->assert(
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
    public function assertNotMessage($message)
    {
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
    protected function assertValidMessageTable(TableNode $messages, $expected_header)
    {
        // Check that the table only contains a single column.
        $header_row = $messages->getRow(0);

        $column_count = count($header_row);
        if ($column_count != 1) {
            throw new \RuntimeException("The list of $expected_header should only contain 1 column. It has $column_count columns.");
        }

        // Check that the correct header is used.
        $actual_header = reset($header_row);
        if (strtolower(trim($actual_header)) !== $expected_header) {
            $capitalized_header = ucfirst($expected_header);
            throw new \RuntimeException("The list of $expected_header should have the header '$capitalized_header'.");
        }
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
    private function assert($message, $selectorId, $exceptionMsgNone, $exceptionMsgMissing)
    {
        $selector = $this->getDrupalSelector($selectorId);
        $selectorObjects = $this->getSession()->getPage()->findAll("css", $selector);
        if (empty($selectorObjects)) {
            throw new ExpectationException(sprintf($exceptionMsgNone, $this->getSession()->getCurrentUrl()), $this->getSession()->getDriver());
        }
        foreach ($selectorObjects as $selectorObject) {
            if (strpos(trim($selectorObject->getText()), $message) !== false) {
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
    private function assertNot($message, $selectorId, $exceptionMsg)
    {
        $selector = $this->getDrupalSelector($selectorId);
        $selectorObjects = $this->getSession()->getPage()->findAll("css", $selector);
        if (!empty($selectorObjects)) {
            foreach ($selectorObjects as $selectorObject) {
                if (strpos(trim($selectorObject->getText()), $message) !== false) {
                    throw new ExpectationException(sprintf($exceptionMsg, $this->getSession()->getCurrentUrl(), $message), $this->getSession()->getDriver());
                }
            }
        }
    }
}
