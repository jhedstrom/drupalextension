<?php

declare(strict_types=1);

namespace Drupal\DrupalExtension\Tests\Fixtures;

/**
 * Abstract context for testing.
 */
abstract class AbstractContext
{

    /**
     * Abstract step.
     *
     * @Given I am abstract
     *
     * @code
     * Given I am abstract
     * @endcode
     */
    public function abstractStep(): void
    {
    }
}
