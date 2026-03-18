<?php

declare(strict_types=1);

namespace Drupal\DrupalExtension\Tests;

use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;

/**
 * Tests basic PHPUnit functionality.
 */
#[CoversNothing]
class ExampleTest extends TestCase {

  /**
   * Tests basic addition.
   */
  public function testAddition(): void {
    $this->assertEquals(2, 1 + 1);
  }

}
