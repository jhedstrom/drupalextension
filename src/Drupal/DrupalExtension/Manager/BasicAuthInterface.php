<?php

declare(strict_types=1);

namespace Drupal\DrupalExtension\Manager;

/**
 * Interface for authentication managers that apply HTTP Basic auth.
 */
interface BasicAuthInterface {

  /**
   * Applies configured HTTP Basic authentication credentials to the session.
   *
   * Resetting a Mink session clears request headers, which drops any basic
   * auth credentials. Calling this restores them so requests to sites behind
   * webserver-level basic auth stay authenticated after a reset.
   *
   * Credentials come from the 'basic_auth' configuration, falling back to the
   * 'base_url' userinfo. Drivers that cannot set basic auth (such as
   * JavaScript drivers) are a no-op.
   */
  public function applyBasicAuth(): void;

}
