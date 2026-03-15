<?php

declare(strict_types=1);

namespace Drupal\DrupalExtension\Tests\Fixtures;

/**
 * Test fixture for method without step annotations.
 *
 * This is used to test sorting when methods don't have.
 *
 * @Given/@When/@Then annotations.
 */
class NoStepAnnotationContext {

  /**
   * This method has a description but no step annotation.
   */
  public function nostepannotationMethodWithoutStepAnnotation(): void {
  }

}
