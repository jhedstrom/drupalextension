<?php

namespace Drupal\DrupalExtension\Manager;

/**
 * Interface for authentication managers that support fast logout.
 */
interface FastLogoutInterface
{
    /**
     * Logs out by directly resetting the session.
     *
     * A fast logout method that resets the session and doesn't need to
     * bootstrap Drupal. This should not be used if logout hooks need to fire.
     */
    public function fastLogout();
}
