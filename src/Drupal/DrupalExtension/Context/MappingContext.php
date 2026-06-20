<?php

declare(strict_types=1);

namespace Drupal\DrupalExtension\Context;

use Behat\Behat\Context\Context;
use Behat\Gherkin\Node\TableNode;
use Behat\Transformation\Transform;
use Drupal\DrupalExtension\ParametersAwareInterface;
use Drupal\DrupalExtension\ParametersTrait;

/**
 * Replaces '{{ Key }}' tokens in step text and tables with mapped values.
 *
 * Mappings are configured under 'Drupal\DrupalExtension: mappings:' as named
 * groups of key/value pairs. A token of the form '{{ Key }}' in any step
 * argument or table cell is replaced with the value mapped to 'Key' before
 * the step runs. Whitespace immediately inside the braces is ignored, so
 * '{{ Key }}' and '{{Key}}' resolve identically.
 *
 * Operates purely on Gherkin step text - no Mink session, no Drupal driver -
 * so it can be registered in any Behat suite. Resolution keys off the token's
 * own '{{ }}' delimiters, never the argument's placeholder name, so a single
 * map covers every step that takes a string without the step having to opt in.
 *
 * An unknown key fails the step immediately rather than passing the token
 * through unresolved, so a typo surfaces as an error instead of a silent
 * mismatch against literal text.
 */
class MappingContext implements Context, ParametersAwareInterface {

  use ParametersTrait;

  /**
   * Matches one '{{ Key }}' token, capturing the still-untrimmed key.
   */
  protected const TOKEN_REGEX = '#\{\{(.+?)\}\}#';

  /**
   * Replaces every '{{ Key }}' token inside a scalar step argument.
   *
   * @param string $argument
   *   The raw step argument.
   *
   * @return string
   *   The argument with every mapping token resolved.
   */
  #[Transform('#(.*\{\{.+?\}\}.*)#')]
  public function transformMappings(string $argument): string {
    return $this->substitute($argument);
  }

  /**
   * Replaces every '{{ Key }}' token inside a table's cells.
   *
   * @param \Behat\Gherkin\Node\TableNode $table
   *   The raw table argument.
   *
   * @return \Behat\Gherkin\Node\TableNode
   *   A new table with every cell's mapping tokens resolved.
   */
  #[Transform('table:*')]
  public function transformMappingsTable(TableNode $table): TableNode {
    $rows = [];

    foreach ($table->getRows() as $row) {
      $rows[] = array_map($this->substitute(...), $row);
    }

    return new TableNode($rows);
  }

  /**
   * Substitutes every mapping token found in a single string.
   *
   * @param string $value
   *   The string to resolve tokens in.
   *
   * @return string
   *   The string with every '{{ Key }}' replaced by its mapped value.
   */
  protected function substitute(string $value): string {
    $result = preg_replace_callback(self::TOKEN_REGEX, fn (array $match): string => (string) $this->getMapping(trim($match[1])), $value);

    return $result ?? $value;
  }

}
