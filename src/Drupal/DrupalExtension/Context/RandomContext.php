<?php

declare(strict_types=1);

namespace Drupal\DrupalExtension\Context;

use Behat\Behat\Context\Context;
use Behat\Behat\Hook\Scope\ScenarioScope;
use Behat\Gherkin\Node\TableNode;
use Behat\Hook\AfterScenario;
use Behat\Hook\BeforeScenario;
use Behat\Transformation\Transform;
use Drupal\Component\Utility\Random;

/**
 * Transforms tokens in step text and tables into random values.
 *
 * Operates purely on Gherkin step text - no Mink session, no Drupal
 * driver - so it can be registered in any Behat suite, including ones
 * that load neither 'Drupal\MinkExtension' nor 'Drupal\DrupalExtension'.
 *
 * Tokens take the form '[?<name>:<type>[,<args>]]', carrying an identity,
 * a generator type, and optional type args.
 *
 * Each unique token (identity + type + args) resolves once per scenario
 * and re-using the same literal returns the same value. The default type
 * is 'string' with length '10', so '[?title]', '[?title:string]', and
 * '[?title:string,10]' all share the same cached value within a scenario.
 *
 * See 'docs/writing-tests.md' for the full list of built-in types.
 */
class RandomContext implements Context {

  protected const BRACKET_REGEX = '#(\[\?[a-z0-9_]+(?::[^\]]+)?\])#i';

  /**
   * Token literal as it appears in the feature file -> canonical cache key.
   *
   * Parsing memo so the same literal does not get re-parsed on every
   * transform invocation.
   *
   * @var array<string, string>
   */
  protected array $literals = [];

  /**
   * Canonical cache key -> generated value.
   *
   * Canonical key is 'name:type:arg1,arg2,...' with defaults applied,
   * so '[?title]', '[?title:string]' and '[?title:string,10]' collapse
   * to the same key.
   *
   * @var array<string, string|int>
   */
  protected array $values = [];

  /**
   * Random string generator (lazy).
   */
  protected ?Random $random = NULL;

  /**
   * Substitutes '[?...]' tokens inside a step argument.
   *
   * @return string|array<int, string>|null
   *   The transformed message.
   */
  #[Transform('#(.*\[\?[a-z0-9_]+(?::[^\]]+)?\].*)#i')]
  public function transformVariables(string $message): string|array|null {
    return $this->substitute($message);
  }

  /**
   * Substitutes '[?...]' tokens inside table cells.
   */
  #[Transform('table:*')]
  public function transformTable(TableNode $table): TableNode {
    return $this->substituteTable($table);
  }

  /**
   * Pre-resolves every token literal found in the current scenario.
   *
   * Running this in a 'BeforeScenario' hook means the cache is warm by
   * the time the first step runs, which keeps repeated literals stable
   * even when their first occurrence is inside a step argument that
   * Behat dispatches before the rest are visited.
   */
  #[BeforeScenario]
  public function beforeScenarioSetVariables(ScenarioScope $scope): void {
    $this->values = [];
    $this->literals = [];

    $steps = [];

    if ($scope->getFeature()->hasBackground()) {
      $steps = $scope->getFeature()->getBackground()->getSteps();
    }

    $steps = array_merge($steps, $scope->getScenario()->getSteps());
    foreach ($steps as $step) {
      $haystack = $step->getText();
      $step_argument = $step->getArguments();

      if (!empty($step_argument) && $step_argument[0] instanceof TableNode) {
        $haystack .= "\n" . $step_argument[0]->getTableAsString();
      }

      preg_match_all(self::BRACKET_REGEX, $haystack, $matches);

      foreach ($matches[0] as $literal) {
        $this->resolveLiteral($literal);
      }
    }
  }

  /**
   * Clears the per-scenario cache.
   */
  #[AfterScenario]
  public function afterScenarioResetVariables(ScenarioScope $scope): void {
    $this->values = [];
    $this->literals = [];
  }

  /**
   * Substitutes every token match in '$message' via 'resolveLiteral()'.
   */
  protected function substitute(string $message): string {
    preg_match_all(self::BRACKET_REGEX, $message, $matches);

    if ($matches[0] === []) {
      return $message;
    }

    $patterns = [];
    $replacements = [];
    foreach ($matches[0] as $literal) {
      $patterns[] = '#' . preg_quote($literal) . '#';
      $replacements[] = (string) $this->resolveLiteral($literal);
    }

    $result = preg_replace($patterns, $replacements, $message);

    return $result ?? $message;
  }

  /**
   * Applies 'substitute()' across every cell in '$table'.
   */
  protected function substituteTable(TableNode $table): TableNode {
    $rows = [];
    foreach ($table->getRows() as $row) {
      $rows[] = array_map($this->substitute(...), $row);
    }

    return new TableNode($rows);
  }

  /**
   * Resolves a token literal to its generated value.
   *
   * On first encounter the literal is parsed, normalised to a canonical
   * '(name, type, args)' tuple, the value is generated and stored under
   * the canonical key, and the literal is recorded in the parsing memo
   * so future lookups are O(1).
   */
  protected function resolveLiteral(string $literal): string|int {
    if (isset($this->literals[$literal])) {
      return $this->values[$this->literals[$literal]];
    }

    [$name, $type, $args] = $this->parseToken($literal);
    $args = $this->normaliseArgs($type, $args);
    $key = $name . ':' . $type . ':' . implode(',', $args);
    $this->literals[$literal] = $key;

    if (!isset($this->values[$key])) {
      $this->values[$key] = $this->generate($type, $args);
    }

    return $this->values[$key];
  }

  /**
   * Parses a token literal into '[name, type, args]'.
   *
   * @return array{0: string, 1: string, 2: list<string>}
   *   Tuple of name, type, and args.
   */
  protected function parseToken(string $literal): array {
    $body = substr($literal, 2, -1);
    $colon = strpos($body, ':');

    if ($colon === FALSE) {
      return [$body, 'string', []];
    }

    $name = substr($body, 0, $colon);
    $spec = substr($body, $colon + 1);
    $parts = array_map(trim(...), explode(',', $spec));
    $type = array_shift($parts);

    return [$name, $type === '' ? 'string' : $type, $parts];
  }

  /**
   * Validates and fills defaults so equivalent tokens share a cache key.
   *
   * Each branch returns the canonical args list for the given type, or
   * throws 'InvalidArgumentException' when the literal supplies malformed
   * input (non-integer length, wrong arg count, extra args on argless
   * types). Failing fast prevents typos like '[?title:string,abc]' from
   * silently producing a 1-character string.
   *
   * @param string $type
   *   The generator type extracted from the token.
   * @param list<string> $args
   *   Raw args parsed from the token literal.
   *
   * @return list<string>
   *   Args with type-specific defaults applied.
   */
  protected function normaliseArgs(string $type, array $args): array {
    return match ($type) {
      'string', 'name', 'machine_name' => $this->normaliseLengthArgs($type, $args),
      'int' => $this->normaliseIntArgs($args),
      'email', 'uuid' => $this->normaliseArglessArgs($type, $args),
      default => $args,
    };
  }

  /**
   * Validates length-style args (one optional non-negative integer).
   *
   * @param string $type
   *   The generator type, used for error messages.
   * @param list<string> $args
   *   Raw args parsed from the token literal.
   *
   * @return list<string>
   *   Args with the default length filled in if absent.
   */
  protected function normaliseLengthArgs(string $type, array $args): array {
    if (count($args) > 1) {
      throw new \InvalidArgumentException(sprintf('Type "%s" accepts at most one argument (length); got %d.', $type, count($args)));
    }

    if (!isset($args[0])) {
      return ['10'];
    }

    if (!ctype_digit($args[0])) {
      throw new \InvalidArgumentException(sprintf('Type "%s" length must be a non-negative integer; got "%s".', $type, $args[0]));
    }

    return [$args[0]];
  }

  /**
   * Validates 'int' args (zero args for full range, or two integer bounds).
   *
   * @param list<string> $args
   *   Raw args parsed from the token literal.
   *
   * @return list<string>
   *   Args with default range filled in if absent.
   */
  protected function normaliseIntArgs(array $args): array {
    if ($args === []) {
      return ['0', (string) PHP_INT_MAX];
    }

    if (count($args) !== 2) {
      throw new \InvalidArgumentException(sprintf('Type "int" accepts no args (full range) or two args (min, max); got %d.', count($args)));
    }

    foreach ($args as $arg) {
      if (preg_match('/^-?\d+$/', $arg) !== 1) {
        throw new \InvalidArgumentException(sprintf('Type "int" args must be integers; got "%s".', $arg));
      }
    }

    return [$args[0], $args[1]];
  }

  /**
   * Validates argless types ('email', 'uuid'): refuses any positional args.
   *
   * @param string $type
   *   The generator type, used for error messages.
   * @param list<string> $args
   *   Raw args parsed from the token literal.
   *
   * @return list<string>
   *   Always empty.
   */
  protected function normaliseArglessArgs(string $type, array $args): array {
    if ($args !== []) {
      throw new \InvalidArgumentException(sprintf('Type "%s" does not accept arguments; got %d.', $type, count($args)));
    }

    return [];
  }

  /**
   * Dispatches to the type-specific generator.
   *
   * Args are validated by 'normaliseArgs()' before reaching this method,
   * so the casts are safe and not a fallback.
   *
   * @param string $type
   *   The generator type extracted from the token.
   * @param list<string> $args
   *   Already normalised args from 'normaliseArgs()'.
   *
   * @return string|int
   *   The generated value.
   */
  protected function generate(string $type, array $args): string|int {
    return match ($type) {
      'string' => $this->generateString((int) $args[0]),
      'name' => $this->generateName((int) $args[0]),
      'machine_name' => $this->generateMachineName((int) $args[0]),
      'int' => $this->generateInt((int) $args[0], (int) $args[1]),
      'email' => $this->generateEmail(),
      'uuid' => $this->generateUuid(),
      default => throw new \InvalidArgumentException(sprintf('Unknown random token type "%s".', $type)),
    };
  }

  /**
   * Generates a lowercase string - the default for unknown shape requests.
   */
  protected function generateString(int $length): string {
    return strtolower((string) $this->getRandom()->name(max(1, $length)));
  }

  /**
   * Generates a 'Random::name()' string with original case preserved.
   */
  protected function generateName(int $length): string {
    return (string) $this->getRandom()->name(max(1, $length));
  }

  /**
   * Generates a Drupal-shaped machine name (lowercase + underscores).
   */
  protected function generateMachineName(int $length): string {
    return $this->getRandom()->machineName(max(1, $length));
  }

  /**
   * Generates a random integer in '[min, max]' inclusive.
   */
  protected function generateInt(int $min, int $max): int {
    if ($min > $max) {
      [$min, $max] = [$max, $min];
    }

    return random_int($min, $max);
  }

  /**
   * Generates a syntactically valid email at the reserved '.test' TLD.
   *
   * RFC 6761 reserves '.test' for testing, so generated addresses can
   * never collide with real domains.
   */
  protected function generateEmail(): string {
    return strtolower(sprintf(
      '%s@%s.test',
      (string) $this->getRandom()->name(8),
      (string) $this->getRandom()->name(6),
    ));
  }

  /**
   * Generates a UUID v4 string.
   */
  protected function generateUuid(): string {
    $data = random_bytes(16);
    $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
    $data[8] = chr(ord($data[8]) & 0x3f | 0x80);

    return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
  }

  /**
   * Lazily resolves the random string generator.
   */
  protected function getRandom(): Random {
    return $this->random ??= new Random();
  }

}
