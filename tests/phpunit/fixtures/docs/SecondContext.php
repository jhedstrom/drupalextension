<?php

declare(strict_types=1);

namespace Drupal\DrupalExtension\Tests\Fixtures;

/**
 * Second context for testing.
 */
class SecondContext
{

    /**
     * Second method.
     *
     * @Then the second should pass
     *
     * @code
     * Then the second should pass
     * @endcode
     */
    public function secondAssertSecond(): void
    {
    }
}
