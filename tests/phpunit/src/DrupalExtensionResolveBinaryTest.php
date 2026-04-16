<?php

declare(strict_types=1);

namespace Drupal\DrupalExtension\Tests;

use Drupal\DrupalExtension\ServiceContainer\DrupalExtension;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\TestCase;

/**
 * Tests DrupalExtension::resolveBinaryPath().
 */
#[CoversMethod(DrupalExtension::class, 'resolveBinaryPath')]
class DrupalExtensionResolveBinaryTest extends TestCase {

  /**
   * Temporary directory for test fixtures.
   */
  private static string $fixtureDir;

  /**
   * Original working directory to restore after tests.
   */
  private string $originalCwd;

  /**
   * {@inheritdoc}
   */
  public static function setUpBeforeClass(): void {
    self::$fixtureDir = sys_get_temp_dir() . '/drupalext_test_' . getmypid();

    mkdir(self::$fixtureDir . '/project/vendor/bin', 0777, TRUE);
    touch(self::$fixtureDir . '/project/vendor/bin/drush');
    chmod(self::$fixtureDir . '/project/vendor/bin/drush', 0755);

    mkdir(self::$fixtureDir . '/project/web', 0777, TRUE);
  }

  /**
   * {@inheritdoc}
   */
  public static function tearDownAfterClass(): void {
    $iterator = new \RecursiveIteratorIterator(
      new \RecursiveDirectoryIterator(self::$fixtureDir, \FilesystemIterator::SKIP_DOTS),
      \RecursiveIteratorIterator::CHILD_FIRST
    );

    foreach ($iterator as $file) {
      $file->isDir() ? rmdir($file->getPathname()) : unlink($file->getPathname());
    }

    rmdir(self::$fixtureDir);
  }

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    $this->originalCwd = (string) getcwd();
  }

  /**
   * {@inheritdoc}
   */
  protected function tearDown(): void {
    chdir($this->originalCwd);
  }

  /**
   * Tests that absolute paths are returned as-is.
   */
  public function testAbsolutePathReturnedAsIs(): void {
    $this->assertSame('/usr/local/bin/drush', DrupalExtension::resolveBinaryPath('/usr/local/bin/drush'));
  }

  /**
   * Tests that bare command names (no directory separator) are returned as-is.
   */
  public function testBareCommandReturnedAsIs(): void {
    $this->assertSame('drush', DrupalExtension::resolveBinaryPath('drush'));
  }

  /**
   * Tests that a relative path is resolved from the current working directory.
   */
  public function testResolvesFromCwd(): void {
    chdir(self::$fixtureDir . '/project');

    $this->assertSame(
      self::$fixtureDir . '/project/vendor/bin/drush',
      DrupalExtension::resolveBinaryPath('vendor/bin/drush')
    );
  }

  /**
   * Tests that a relative path is resolved from the parent directory.
   */
  public function testResolvesFromParentDir(): void {
    chdir(self::$fixtureDir . '/project/web');

    $this->assertSame(
      self::$fixtureDir . '/project/vendor/bin/drush',
      DrupalExtension::resolveBinaryPath('vendor/bin/drush')
    );
  }

  /**
   * Tests that an unresolvable relative path is returned as-is.
   */
  public function testUnresolvableReturnedAsIs(): void {
    chdir(self::$fixtureDir);

    $this->assertSame('some/nonexistent/binary', DrupalExtension::resolveBinaryPath('some/nonexistent/binary'));
  }

}
