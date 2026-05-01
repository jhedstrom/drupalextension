<?php

declare(strict_types=1);

namespace Drupal\DrupalExtension\Tests;

use Behat\Testwork\Suite\Suite;
use Drupal\DrupalExtension\Generator\ClassGenerator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Tests the starter context class generator.
 */
#[CoversClass(ClassGenerator::class)]
class ClassGeneratorTest extends TestCase {

  /**
   * Tests that the generator declares support for any suite and class.
   */
  public function testSupportsSuiteAndClassReturnsTrue(): void {
    $generator = new ClassGenerator();
    $suite = $this->createMock(Suite::class);

    $this->assertTrue($generator->supportsSuiteAndClass($suite, 'Anything'));
  }

  /**
   * Tests starter class output for namespaced and rootless context classes.
   *
   * @param string $context_class
   *   Fully qualified class name passed to the generator.
   * @param string $expected
   *   Exact expected generated source.
   */
  #[DataProvider('dataProviderGenerateClass')]
  public function testGenerateClass(string $context_class, string $expected): void {
    $generator = new ClassGenerator();
    $suite = $this->createMock(Suite::class);

    $this->assertSame($expected, $generator->generateClass($suite, $context_class));
  }

  /**
   * Provides FQCN inputs and the exact source the generator should emit.
   *
   * @return \Iterator<string, array{string, string}>
   *   Cases keyed by description, each [context class FQCN, expected source].
   */
  public static function dataProviderGenerateClass(): \Iterator {
    $namespaced = <<<'PHP'
<?php

namespace App\Tests\Behat;

use Drupal\DrupalExtension\Context\RawDrupalContext;
use Behat\Gherkin\Node\PyStringNode;
use Behat\Gherkin\Node\TableNode;
use Behat\Behat\Tester\Exception\PendingException;

/**
 * Defines application features from the specific context.
 */
class FeatureContext extends RawDrupalContext {

  /**
   * Initializes context.
   *
   * Every scenario gets its own context instance.
   * You can also pass arbitrary arguments to the
   * context constructor through behat.yml.
   */
  public function __construct() {
  }

}

PHP;

    $rootless = <<<'PHP'
<?php

use Drupal\DrupalExtension\Context\RawDrupalContext;
use Behat\Gherkin\Node\PyStringNode;
use Behat\Gherkin\Node\TableNode;
use Behat\Behat\Tester\Exception\PendingException;

/**
 * Defines application features from the specific context.
 */
class FeatureContext extends RawDrupalContext {

  /**
   * Initializes context.
   *
   * Every scenario gets its own context instance.
   * You can also pass arbitrary arguments to the
   * context constructor through behat.yml.
   */
  public function __construct() {
  }

}

PHP;

    yield 'fully qualified class name' => ['App\\Tests\\Behat\\FeatureContext', $namespaced];
    yield 'class without namespace' => ['FeatureContext', $rootless];
  }

}
