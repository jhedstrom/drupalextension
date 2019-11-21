<?php

namespace Drupal\DrupalExtension\Manager;

/**
 * Interface for classes that authenticate users during tests.
 */
interface DrupalAuthenticationManagerInterface
{

    /**
     * Logs in as the given user.
     *
     * @param \stdClass $user
     *   The user to log in.
     *
     * @param array $extra_fields
     *   Extra fields used during log in.
     */
    public function logIn(\stdClass $user, $extra_fields = array());

    /**
     * Logs the current user out.
     */
    public function logOut();

    /**
     * Determine if a user is already logged in.
     *
     * @return bool
     *   Returns TRUE if a user is logged in for this session.
     */
    public function loggedIn();
}
