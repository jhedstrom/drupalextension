<?php

declare(strict_types=1);

/**
 * @file
 * Contains \Drupal\DrupalExtension\Context\ConfigContext.
 */
namespace Drupal\DrupalExtension\Context;

use Behat\Hook\AfterScenario;
use Behat\Step\Given;
use Behat\Behat\Context\TranslatableContext;
use Behat\Gherkin\Node\TableNode;
use Drupal\Driver\DrupalDriver;

/**
 * Provides pre-built step definitions for interacting with Drupal config.
 */
class ConfigContext extends RawDrupalContext implements TranslatableContext {

  /**
   * {@inheritdoc}
   */
  public static function getTranslationResources() {
    return self::getDrupalTranslationResources();
  }

  /**
   * Keep track of any config that was changed so they can easily be reverted.
   *
   * @var array
   */
  protected $config = [];

  /**
   * Revert any changed config.
   */
  #[AfterScenario]
  public function cleanConfig(): void {
    $driver = $this->getDriver();

    // Revert config that was changed.
    foreach ($this->config as $name => $keyValue) {
      // Reset the config factory cache so the editable config object is
      // loaded fresh from storage. Without this, the cached object retains
      // stale originalData from when setConfig() ran, causing
      // ConfigCrudEvent::isChanged() to compare against the wrong baseline.
      if ($driver instanceof DrupalDriver) {
        \Drupal::configFactory()->reset($name);
      }

      foreach ($keyValue as $key => $value) {
        $driver->configSet($name, $key, $value);
      }
    }
    $this->config = [];
  }

  /**
   * Sets basic configuration item.
   *
   * @param string $name
   *   The name of the configuration object.
   * @param string $key
   *   Identifier to store value in configuration.
   * @param string $value
   *   Value to associate with identifier.
   *
   * @code
   *   Given I set the configuration item "system.site" with key "name" to "My Site"
   * @endcode
   */
  #[Given('I set the configuration item :name with key :key to :value')]
  public function setBasicConfig(string $name, string $key, string $value): void {
    $this->setConfig($name, $key, $value);
  }

  /**
   * Sets complex configuration.
   *
   * @param string $name
   *   The name of the configuration object.
   * @param string $key
   *   Identifier to store value in configuration.
   * @param \Behat\Gherkin\Node\TableNode $config_table
   *   The table listing configuration keys and values.
   *
   * @code
   *   Given I set the configuration item "system.site" with key "page" with values:
   *     | key   | value  |
   *     | front | /node  |
   *     | 403   | /error |
   * @endcode
   */
  #[Given('I set the configuration item :name with key :key with values:')]
  public function setComplexConfig(string $name, string $key, TableNode $config_table): void {
    $value = [];
    foreach ($config_table->getHash() as $row) {
      // Allow json values for extra complexity.
      $decoded = json_decode($row['value'], TRUE);
      if ($decoded !== NULL) {
        $row['value'] = $decoded;
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
  protected function setConfig(string $name, string $key, mixed $value): void {
    $driver = $this->getDriver();
    if ($driver instanceof DrupalDriver) {
      $backup = $driver->getCore()->configGetOriginal($name, $key);
    }
    else {
      $backup = $driver->configGet($name, $key);
    }
    $driver->configSet($name, $key, $value);
    if (!array_key_exists($name, $this->config)) {
      $this->config[$name][$key] = $backup;
      return;
    }

    if (!array_key_exists($key, $this->config[$name])) {
      $this->config[$name][$key] = $backup;
    }
  }

}
