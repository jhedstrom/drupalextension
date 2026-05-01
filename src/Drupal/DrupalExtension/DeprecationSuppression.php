<?php

declare(strict_types=1);

namespace Drupal\DrupalExtension;

/**
 * Resolves whether deprecation notices should be suppressed.
 *
 * Two inputs feed the decision:
 * - The 'suppress_deprecations' boolean from the extension configuration
 *   (under 'Drupal\DrupalExtension' in 'behat.yml'), surfaced to contexts
 *   through 'ParametersTrait'.
 * - The 'BEHAT_DRUPALEXTENSION_SUPPRESS_DEPRECATIONS' environment variable.
 *   When set to a parseable boolean ('1'/'0', 'true'/'false', 'yes'/'no',
 *   'on'/'off'), it overrides the configuration in either direction. An
 *   unset or unparseable value yields no override.
 *
 * Centralised here so the extension load path
 * ('DrupalExtension::loadParameters') and the context runtime path
 * ('DeprecationTrait::triggerDeprecation') share one resolution rule.
 */
final class DeprecationSuppression {

  /**
   * Environment variable that overrides the 'suppress_deprecations' config.
   */
  public const ENV_VAR = 'BEHAT_DRUPALEXTENSION_SUPPRESS_DEPRECATIONS';

  /**
   * Decides whether deprecation notices should be suppressed.
   *
   * @param bool|null $config_value
   *   The 'suppress_deprecations' configuration value, or NULL when not set.
   *
   * @return bool
   *   TRUE when notices should be suppressed.
   */
  public static function shouldSuppress(?bool $config_value): bool {
    $env = self::readEnvOverride();

    if ($env !== NULL) {
      return $env;
    }

    return $config_value === TRUE;
  }

  /**
   * Reads the env-var override, returning NULL when absent or unparseable.
   */
  private static function readEnvOverride(): ?bool {
    $raw = getenv(self::ENV_VAR);

    if ($raw === FALSE || $raw === '') {
      return NULL;
    }

    return self::parseBool($raw);
  }

  /**
   * Parses a boolean-ish string, returning NULL for unrecognised values.
   */
  private static function parseBool(string $raw): ?bool {
    $normalized = strtolower(trim($raw));

    if (in_array($normalized, ['1', 'true', 'yes', 'on'], TRUE)) {
      return TRUE;
    }

    if (in_array($normalized, ['0', 'false', 'no', 'off'], TRUE)) {
      return FALSE;
    }

    return NULL;
  }

}
