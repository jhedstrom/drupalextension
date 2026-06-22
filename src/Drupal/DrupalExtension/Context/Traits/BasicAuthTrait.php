<?php

declare(strict_types=1);

namespace Drupal\DrupalExtension\Context\Traits;

use Behat\Hook\BeforeScenario;
use Behat\Hook\BeforeStep;
use Drupal\DrupalExtension\Manager\BasicAuthInterface;

/**
 * Keeps HTTP Basic authentication applied across session resets.
 *
 * Mink resets the session before every scenario and on every fast logout,
 * which clears request headers and drops basic auth credentials. Re-applying
 * the credentials on each scenario and step keeps requests to sites behind
 * webserver-level basic auth authenticated.
 *
 * A no-op when no credentials are present, so projects that do not use
 * basic auth pay no cost. Used in the standard 'DrupalContext'. The host
 * class is expected to extend 'RawDrupalContext' so
 * '$this->getAuthenticationManager()' is available.
 */
trait BasicAuthTrait {

  /**
   * Applies basic auth before each scenario, after Mink resets the session.
   */
  #[BeforeScenario]
  public function basicAuthBeforeScenario(): void {
    $this->basicAuthApply();
  }

  /**
   * Re-applies basic auth before each step in case a reset cleared it.
   */
  #[BeforeStep]
  public function basicAuthBeforeStep(): void {
    $this->basicAuthApply();
  }

  /**
   * Applies the resolved basic auth credentials to the session.
   */
  protected function basicAuthApply(): void {
    $manager = $this->getAuthenticationManager();
    if ($manager instanceof BasicAuthInterface) {
      $manager->applyBasicAuth();
    }
  }

}
