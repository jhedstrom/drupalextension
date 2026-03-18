<?php

declare(strict_types=1);

namespace Drupal\DrupalExtension\Tests;

use Drupal\DrupalExtension\ServiceContainer\DrupalExtension;
use Drupal\MinkExtension\ServiceContainer\MinkExtension;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\Definition\ArrayNode;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Yaml\Yaml;

/**
 * Tests the behat.dist.yml starter configuration file.
 *
 * Cross-checks the dist file against the actual configuration tree builders
 * to ensure all schema-defined keys are present and all dist keys are valid.
 */
#[CoversNothing]
class BehatDistYmlTest extends TestCase {

  /**
   * Parsed behat.dist.yml contents.
   */
  private static array $distConfig;

  /**
   * {@inheritdoc}
   */
  public static function setUpBeforeClass(): void {
    parent::setUpBeforeClass();
    $distFile = dirname(__DIR__, 3) . '/behat.dist.yml';
    self::assertFileExists($distFile);
    self::$distConfig = Yaml::parseFile($distFile);
  }

  /**
   * Tests that expected profiles are present.
   */
  public function testContainsExpectedProfiles(): void {
    $this->assertArrayHasKey('default', self::$distConfig, 'Missing "default" profile.');
    $this->assertArrayHasKey('drupal', self::$distConfig, 'Missing "drupal" profile.');
  }

  /**
   * Tests that the DrupalExtension config in behat.dist.yml is valid.
   *
   * Processes the dist file values through the actual config tree builder
   * to ensure all keys are recognized by the schema.
   */
  #[DataProvider('dataProviderProfiles')]
  public function testDrupalExtensionConfigIsValid(string $profile): void {
    $distValues = self::$distConfig[$profile]['extensions']['Drupal\DrupalExtension'] ?? [];
    if ($distValues === NULL) {
      $distValues = [];
    }

    $tree = $this->buildDrupalExtensionTree();

    // Process through the real config tree — throws on unknown keys.
    $processed = $tree->finalize($tree->normalize($distValues));
    $this->assertIsArray($processed);
  }

  /**
   * Tests that behat.dist.yml covers all DrupalExtension schema keys.
   *
   * Extracts keys from the built config tree and asserts each one appears
   * in at least one profile's dist file section.
   */
  public function testDistCoversAllDrupalExtensionKeys(): void {
    $allDistKeys = [];
    foreach (self::$distConfig as $profile) {
      $values = $profile['extensions']['Drupal\DrupalExtension'] ?? [];
      if (is_array($values)) {
        $allDistKeys = array_merge($allDistKeys, array_keys($values));
      }
    }
    $allDistKeys = array_unique($allDistKeys);

    $tree = $this->buildDrupalExtensionTree();

    $schemaKeys = array_keys($tree->getChildren());
    foreach ($schemaKeys as $key) {
      $this->assertContains($key, $allDistKeys, sprintf('behat.dist.yml missing DrupalExtension key "%s" in all profiles.', $key));
    }
  }

  /**
   * Tests that the MinkExtension config in behat.dist.yml is valid.
   *
   * Processes the dist file values through the actual config tree builder.
   * The MinkExtension requires at least one session to be defined.
   */
  public function testMinkExtensionConfigIsValid(): void {
    $distValues = self::$distConfig['default']['extensions']['Drupal\MinkExtension'] ?? [];

    $tree = $this->buildMinkExtensionTree();

    $processed = $tree->finalize($tree->normalize($distValues));
    $this->assertIsArray($processed);
    $this->assertArrayHasKey('ajax_timeout', $processed);
  }

  /**
   * Tests that behat.dist.yml covers all MinkExtension top-level schema keys.
   *
   * Checks that every key defined in the config tree appears in the dist
   * file. The "sessions" key is populated via the shortcut syntax
   * (e.g., "browserkit_http: ~") and is excluded from this check.
   */
  public function testDistCoversAllMinkExtensionKeys(): void {
    $distValues = self::$distConfig['default']['extensions']['Drupal\MinkExtension'] ?? [];

    $tree = $this->buildMinkExtensionTree();

    // Sessions are populated via shortcut syntax normalization, so exclude
    // them from the direct key check. Also exclude internal keys that are
    // not meant for end-user configuration.
    $skipKeys = ['sessions', 'mink_loader', 'default_session'];
    $schemaKeys = array_diff(array_keys($tree->getChildren()), $skipKeys);

    foreach ($schemaKeys as $key) {
      $this->assertArrayHasKey($key, $distValues, sprintf('behat.dist.yml default profile missing MinkExtension key "%s".', $key));
    }
  }

  /**
   * Builds the DrupalExtension config tree.
   */
  private function buildDrupalExtensionTree(): ArrayNode {
    $builder = new ArrayNodeDefinition('drupal');
    (new DrupalExtension())->configure($builder);
    $tree = $builder->getNode(TRUE);
    $this->assertInstanceOf(ArrayNode::class, $tree);
    return $tree;
  }

  /**
   * Builds the MinkExtension config tree.
   */
  private function buildMinkExtensionTree(): ArrayNode {
    $builder = new ArrayNodeDefinition('mink');
    (new MinkExtension())->configure($builder);
    $tree = $builder->getNode(TRUE);
    $this->assertInstanceOf(ArrayNode::class, $tree);
    return $tree;
  }

  /**
   * Data provider for profile-level tests.
   */
  public static function dataProviderProfiles(): \Iterator {
    yield 'default profile' => ['default'];
    yield 'drupal profile' => ['drupal'];
  }

}
