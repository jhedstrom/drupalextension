<?php

namespace Drupal\DrupalExtension\Context;

use Drupal\big_pipe\Render\Placeholder\BigPipeStrategy;
use Behat\Mink\Exception\UnsupportedDriverActionException;

/**
 * Big Pipe context.
 */
class BigPipeContext extends RawDrupalContext {

    /**
     * Prepares Big Pipe NOJS cookie if needed.
     *
     * @BeforeScenario
     */
    public function prepareBigPipeNoJsCookie()
    {
        try {
            // Check if JavaScript can be executed by Driver.
            $this->getSession()->getDriver()->executeScript('true');
        }
        catch (UnsupportedDriverActionException $e) {
            // Set NOJS cookie.
            $this
              ->getSession()
              ->setCookie(BigPipeStrategy::NOJS_COOKIE, true);

        }
        catch (\Exception $e) {
            // Mute exceptions.
        }
    }

}
