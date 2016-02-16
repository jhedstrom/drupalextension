<?php

/**
 * @file
 * Contains \Drupal\DrupalExtension\Context\ConfigContext.
 */

namespace Drupal\DrupalExtension\Context;

use Behat\Behat\Context\TranslatableContext;
use Behat\Gherkin\Node\TableNode;

/**
 * Provides pre-built step definitions for interacting with Drupal config.
 */
class ConfigContext extends RawDrupalContext implements TranslatableContext {

  /**
   * {@inheritDoc}
   */
  public static function getTranslationResources() {
    return glob(__DIR__ . '/../../../../i18n/*.xliff');
  }

  /**
   * Keep track of any config that was changed so they can easily be reverted.
   *
   * @var array
   */
  protected $config = array();

  /**
   * Revert any changed config.
   *
   * @AfterScenario
   */
  public function cleanConfig() {
    // Revert config that was changed.
    foreach ($this->config as $name => $key_value) {
      foreach ($key_value as $key => $value) {
        $this->getDriver()->configSet($name, $key, $value);
      }
    }
    $this->config = array();
  }

  /**
   * Sets basic configuration item.
   *
   * @param string $name
   *   The name of the configuration object.
   * @param string $key
   *   Identifier to store value in configuration.
   * @param mixed $value
   *   Value to associate with identifier.
   *
   * @Given I set the configuration item :name with key :key to :value
   */
  public function setBasicConfig($name, $key, $value) {
    $this->setConfig($name, $key, $value);
  }

  /**
   * Sets complex configuration.
   *
   * @param string $name
   *   The name of the configuration object.
   * @param string $key
   *   Identifier to store value in configuration.
   * @param TableNode $config_table
   *   The table listing configuration keys and values.
   *
   * @Given I set the configuration item :name with key :key with values:
   *
   * Provide configuration data in the following format:
   *  | key   | value  |
   *  | foo   | bar    |
   */
  public function setComplexConfig($name, $key, TableNode $config_table) {
    $value = array();
    foreach ($config_table->getHash() as $row) {
      // Allow json values for extra complexity.
      if (json_decode($row['value'])) {
        $row['value'] = json_decode($row['value'], TRUE);
      }
      $value[$row['key']] = $row['value'];
    }
    $this->setConfig($name, $key, $value);
  }

  /**
   * Sets a value in a configuration object.
   *
   * @param string $name
   *   The name of the configuration object.
   * @param string $key
   *   Identifier to store value in configuration.
   * @param mixed $value
   *   Value to associate with identifier.
   */
  public function setConfig($name, $key, $value) {
    $backup = $this->getDriver()->configGet($name, $key);
    $this->getDriver()->configSet($name, $key, $value);
    $this->config[$name][$key] = $backup;
  }

}