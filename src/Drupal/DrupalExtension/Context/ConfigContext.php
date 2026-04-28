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
use Drupal\Driver\Capability\ConfigCapabilityInterface;
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
    if ($this->config === []) {
      return;
    }

    $driver = $this->getDriver();

    if (!$driver instanceof ConfigCapabilityInterface) {
      return;
    }

    // Revert config that was changed.
    foreach ($this->config as $name => $key_value) {
      // Reset the config factory cache so the editable config object is
      // loaded fresh from storage. Without this, the cached object retains
      // stale originalData from when setConfig() ran, causing
      // ConfigCrudEvent::isChanged() to compare against the wrong baseline.
      if ($driver instanceof DrupalDriver) {
        \Drupal::configFactory()->reset($name);
      }

      foreach ($key_value as $key => $value) {
        $driver->configSet($name, $key, $value);
      }
    }

    $this->config = [];
  }

  /**
   * Sets a configuration item.
   *
   * Scalar type coercion is applied automatically so that values like "true",
   * "false", "null", and numeric strings are stored with their native PHP
   * types rather than as plain strings.
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
   *   Given I set the configuration item "system.performance" with key "css.preprocess" to "false"
   *   Given I set the configuration item "system.site" with key "weight_select_max" to "50"
   *   Given I set the configuration item "some.config" with key "nullable_key" to "null"
   * @endcode
   */
  #[Given('I set the configuration item :name with key :key to :value')]
  public function setBasicConfig(string $name, string $key, string $value): void {
    $this->setConfig($name, $key, static::coerceValue($value));
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
   *   Given I set the configuration item "some.config" with key "settings" with values:
   *     | key     | value                    |
   *     | enabled | true                     |
   *     | count   | 5                        |
   *     | nested  | {"foo": "bar", "baz": 1} |
   * @endcode
   */
  #[Given('I set the configuration item :name with key :key with values:')]
  public function setComplexConfig(string $name, string $key, TableNode $config_table): void {
    $value = [];
    foreach ($config_table->getHash() as $row) {
      $coerced = static::coerceValue($row['value']);
      // If coercion returned the string unchanged, attempt JSON decode to
      // support complex nested structures like {"key": "value"} or [1, 2].
      if (is_string($coerced)) {
        $decoded = json_decode($coerced, TRUE);
        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
          $coerced = $decoded;
        }
      }
      $value[$row['key']] = $coerced;
    }
    $this->setConfig($name, $key, $value);
  }

  /**
   * Coerces a string value to its native PHP type.
   *
   * Behat always passes step arguments as strings. This method converts
   * well-known scalar representations to their proper PHP types so that
   * Drupal configuration receives correctly typed values.
   *
   * @param string $value
   *   The raw string value from a Behat step argument.
   *
   * @return mixed
   *   The coerced value: bool, null, int, float, or the original string.
   */
  protected static function coerceValue(string $value): mixed {
    if ($value === 'true') {
      return TRUE;
    }

    if ($value === 'false') {
      return FALSE;
    }

    if ($value === 'null') {
      return NULL;
    }

    if (is_numeric($value)) {
      return str_contains($value, '.') ? (float) $value : (int) $value;
    }

    return $value;
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

    if (!$driver instanceof ConfigCapabilityInterface) {
      throw new \RuntimeException(sprintf('The active Drupal driver "%s" does not support configuration management.', $driver::class));
    }

    $backup = $driver->configGetOriginal($name, $key);
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
