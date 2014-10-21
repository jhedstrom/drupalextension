<?php

namespace Drupal\DrupalExtension\Context;

use Behat\Behat\Context\TranslatableContext;
use Behat\Gherkin\Node\TableNode;

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
    $errorSelector = $this->getDrupalSelector('error_message_selector');
    $errorSelectorObj = $this->getSession()->getPage()->find("css", $errorSelector);
    if(empty($errorSelectorObj)) {
      throw new \Exception(sprintf("The page '%s' does not contain any error messages", $this->getSession()->getCurrentUrl()));
    }
    if (strpos(trim($errorSelectorObj->getText()), $message) === FALSE) {
      throw new \Exception(sprintf("The page '%s' does not contain the error message '%s'", $this->getSession()->getCurrentUrl(), $message));
    }
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
    $errorSelector = $this->getDrupalSelector('error_message_selector');
    $errorSelectorObj = $this->getSession()->getPage()->find("css", $errorSelector);
    if(!empty($errorSelectorObj)) {
      if (strpos(trim($errorSelectorObj->getText()), $message) !== FALSE) {
        throw new \Exception(sprintf("The page '%s' contains the error message '%s'", $this->getSession()->getCurrentUrl(), $message));
      }
    }
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
    $successSelector = $this->getDrupalSelector('success_message_selector');
    $successSelectorObj = $this->getSession()->getPage()->find("css", $successSelector);
    if(empty($successSelectorObj)) {
      throw new \Exception(sprintf("The page '%s' does not contain any success messages", $this->getSession()->getCurrentUrl()));
    }
    if (strpos(trim($successSelectorObj->getText()), $message) === FALSE) {
      throw new \Exception(sprintf("The page '%s' does not contain the success message '%s'", $this->getSession()->getCurrentUrl(), $message));
    }
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
    $successSelector = $this->getDrupalSelector('success_message_selector');
    $successSelectorObj = $this->getSession()->getPage()->find("css", $successSelector);
    if(!empty($successSelectorObj)) {
      if (strpos(trim($successSelectorObj->getText()), $message) !== FALSE) {
        throw new \Exception(sprintf("The page '%s' contains the success message '%s'", $this->getSession()->getCurrentUrl(), $message));
      }
    }
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
    $warningSelector = $this->getDrupalSelector('warning_message_selector');
    $warningSelectorObj = $this->getSession()->getPage()->find("css", $warningSelector);
    if(empty($warningSelectorObj)) {
      throw new \Exception(sprintf("The page '%s' does not contain any warning messages", $this->getSession()->getCurrentUrl()));
    }
    if (strpos(trim($warningSelectorObj->getText()), $message) === FALSE) {
      throw new \Exception(sprintf("The page '%s' does not contain the warning message '%s'", $this->getSession()->getCurrentUrl(), $message));
    }
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
    $warningSelector = $this->getDrupalSelector('warning_message_selector');
    $warningSelectorObj = $this->getSession()->getPage()->find("css", $warningSelector);
    if(!empty($warningSelectorObj)) {
      if (strpos(trim($warningSelectorObj->getText()), $message) !== FALSE) {
        throw new \Exception(sprintf("The page '%s' contains the warning message '%s'", $this->getSession()->getCurrentUrl(), $message));
      }
    }
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
    $msgSelector = $this->getDrupalSelector('message_selector');
    $msgSelectorObj = $this->getSession()->getPage()->find("css", $msgSelector);
    if(empty($msgSelectorObj)) {
      throw new \Exception(sprintf("The page '%s' does not contain any messages", $this->getSession()->getCurrentUrl()));
    }
    if (strpos(trim($msgSelectorObj->getText()), $message) === FALSE) {
      throw new \Exception(sprintf("The page '%s' does not contain the message '%s'", $this->getSession()->getCurrentUrl(), $message));
    }
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
    $msgSelector = $this->getDrupalSelector('message_selector');
    $msgSelectorObj = $this->getSession()->getPage()->find("css", $msgSelector);
    if(!empty($msgSelectorObj)) {
      if (strpos(trim($msgSelectorObj->getText()), $message) !== FALSE) {
        throw new \Exception(sprintf("The page '%s' contains the message '%s'", $this->getSession()->getCurrentUrl(), $message));
      }
    }
  }

  /**
   * Returns a specific css selector.
   *
   * @param $name
   *   string CSS selector name
   */
  public function getDrupalSelector($name) {
    $text = $this->getDrupalParameter('selectors');
    if (!isset($text[$name])) {
      throw new \Exception(sprintf('No such selector configured: %s', $name));
    }
    return $text[$name];
  }

}
