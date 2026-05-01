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
use Drupal\DrupalExtension\DeprecationInterface;
use Drupal\DrupalExtension\DeprecationTrait;
use Drupal\DrupalExtension\ParametersAwareInterface;

/**
 * Transforms tokens in step text and tables into random values.
 *
 * Operates purely on Gherkin step text - no Mink session, no Drupal
 * driver - so it can be registered in any Behat suite, including ones
 * that load neither 'Drupal\MinkExtension' nor 'Drupal\DrupalExtension'.
 *
 * Two token forms are supported:
 *
 *   '[?<name>:<type>[,<args>]]' - identity, type, and optional type args.
 *   '<?<name>>'                 - legacy, deprecated; equivalent to
 *                                 '[?<name>:string,10]'.
 *
 * Each unique token (identity + type + args) resolves once per scenario
 * and re-using the same literal returns the same value. The default type
 * is 'string' with length '10', so '[?title]', '[?title:string]', and
 * the legacy '<?title>' all share the same cached value within a
 * scenario, easing incremental migration.
 *
 * See 'docs/writing-tests.md' for the full list of built-in types.
 */
class RandomContext implements Context, ParametersAwareInterface, DeprecationInterface {

  use DeprecationTrait;

  protected const SCAN_REGEX = '#(\[\?[a-z0-9_]+(?::[^\]]+)?\]|\<\?[a-z0-9_]+\>)#i';

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
   * so '[?title]', '[?title:string]', '[?title:string,10]' and the
   * legacy '<?title>' collapse to the same key.
   *
   * @var array<string, string|int>
   */
  protected array $values = [];

  /**
   * Random string generator (lazy).
   */
  protected ?Random $random = NULL;

  /**
   * Transforms tokens inside any step argument that contains one.
   *
   * The regex is intentionally permissive - the precise scan happens
   * inside this method; the 'Transform' attribute just routes any
   * argument that contains a '<?' or '[?' marker through here.
   *
   * @return string|array<int, string>|null
   *   The transformed message.
   */
  #[Transform('#(.*(?:\<\?|\[\?).*)#')]
  public function transformVariables(string $message): string|array|null {
    $patterns = [];
    $replacements = [];

    preg_match_all(self::SCAN_REGEX, $message, $matches);
    foreach ($matches[0] as $literal) {
      $patterns[] = '#' . preg_quote($literal) . '#';
      $replacements[] = (string) $this->resolveLiteral($literal);
    }

    return preg_replace($patterns, $replacements, $message);
  }

  /**
   * Transforms tokens inside table arguments.
   */
  #[Transform('table:*')]
  public function transformTable(TableNode $table): TableNode {
    $rows = [];
    foreach ($table->getRows() as $row) {
      $transformed = array_map($this->transformVariables(...), $row);
      $rows[] = array_map(static fn (array|string|null $v): string => is_array($v) ? implode(',', $v) : (string) $v, $transformed);
    }

    return new TableNode($rows);
  }

  /**
   * Pre-resolves every token literal found in the current scenario.
   *
   * Running this in a 'BeforeScenario' hook means the cache is warm by
   * the time the first step runs, and emitted deprecation messages are
   * grouped at scenario start instead of interleaved with step output.
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

      preg_match_all(self::SCAN_REGEX, $haystack, $matches);
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
   * Resolves a token literal to its generated value.
   *
   * On first encounter the literal is parsed, normalised to a canonical
   * '(name, type, args)' tuple, the value is generated and stored under
   * the canonical key, and the literal is recorded in the parsing memo
   * so future lookups are O(1). Legacy '<?...>' literals additionally
   * trigger a single deprecation notice.
   */
  protected function resolveLiteral(string $literal): string|int {
    if (isset($this->literals[$literal])) {
      return $this->values[$this->literals[$literal]];
    }

    [$name, $type, $args, $legacy] = $this->parseToken($literal);

    if ($legacy) {
      $this->triggerDeprecation(sprintf(
        'The "%s" token syntax is deprecated. Use "[?%s]" instead.',
        $literal,
        $name,
      ));
    }

    $args = $this->normaliseArgs($type, $args);
    $key = $name . ':' . $type . ':' . implode(',', $args);
    $this->literals[$literal] = $key;

    if (!isset($this->values[$key])) {
      $this->values[$key] = $this->generate($type, $args);
    }

    return $this->values[$key];
  }

  /**
   * Parses a token literal into '[name, type, args, legacy]'.
   *
   * @return array{0: string, 1: string, 2: list<string>, 3: bool}
   *   Tuple of name, type, args, and legacy flag.
   */
  protected function parseToken(string $literal): array {
    if (str_starts_with($literal, '<?')) {
      return [substr($literal, 2, -1), 'string', [], TRUE];
    }

    $body = substr($literal, 2, -1);
    $colon = strpos($body, ':');

    if ($colon === FALSE) {
      return [$body, 'string', [], FALSE];
    }

    $name = substr($body, 0, $colon);
    $spec = substr($body, $colon + 1);
    $parts = array_map(trim(...), explode(',', $spec));
    $type = array_shift($parts);

    return [$name, $type === '' ? 'string' : $type, $parts, FALSE];
  }

  /**
   * Fills in defaults so equivalent tokens collapse to the same cache key.
   *
   * Each branch returns the canonical args list for the given type.
   * Unknown types pass through unchanged - 'generate()' will reject them.
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
      'string', 'name', 'machine_name' => [$args[0] ?? '10'],
      'int' => [$args[0] ?? '0', $args[1] ?? (string) PHP_INT_MAX],
      'email', 'uuid' => [],
      default => $args,
    };
  }

  /**
   * Dispatches to the type-specific generator.
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
      'string' => $this->generateString((int) ($args[0] ?? 10)),
      'name' => $this->generateName((int) ($args[0] ?? 10)),
      'machine_name' => $this->generateMachineName((int) ($args[0] ?? 10)),
      'int' => $this->generateInt((int) ($args[0] ?? 0), (int) ($args[1] ?? PHP_INT_MAX)),
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
