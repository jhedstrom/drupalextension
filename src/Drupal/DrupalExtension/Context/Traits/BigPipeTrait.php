<?php

declare(strict_types=1);

namespace Drupal\DrupalExtension\Context\Traits;

use Behat\Behat\Hook\Scope\BeforeScenarioScope;
use Behat\Hook\BeforeScenario;
use Behat\Hook\BeforeStep;
use Behat\Mink\Exception\DriverException;
use Behat\Mink\Exception\UnsupportedDriverActionException;

/**
 * Bypasses BigPipe streaming for non-JavaScript Mink drivers.
 *
 * BigPipe replaces parts of authenticated-user pages with placeholder
 * markup that is filled in by streaming JavaScript. Mink's BrowserKit /
 * Goutte drivers do not execute JavaScript and do not follow the
 * 'http-equiv=refresh' fallback, so authenticated-user assertions silently
 * miss messages and blocks. Setting the 'big_pipe_nojs' cookie tells
 * Drupal to render the page fully server-side, restoring the markup the
 * test expects.
 *
 * Per-scenario opt-out: tag the scenario with
 * '@behat-steps-skip:bigPipeBeforeScenario' (suppresses both hooks) or
 * '@behat-steps-skip:bigPipeBeforeStep' (suppresses only the per-step
 * cookie reset). Globally disable via the 'big_pipe.bypass' extension
 * option.
 *
 * Used in the standard 'DrupalContext'. The host class is expected to
 * extend 'RawDrupalContext' (so '$this->getSession()' and
 * '$this->getDrupalParameter()' are available).
 *
 * @see https://github.com/jhedstrom/drupalextension/issues/258
 */
trait BigPipeTrait {

  /**
   * Cookie name read by BigPipe to bypass streaming and render server-side.
   *
   * Hardcoded to avoid a hard dependency on the big_pipe module - the value
   * is the same string ('big_pipe_nojs') as
   * 'Drupal\big_pipe\Render\Placeholder\BigPipeStrategy::NOJS_COOKIE'.
   */
  private const BIG_PIPE_NOJS_COOKIE = 'big_pipe_nojs';

  /**
   * Whether the active driver supports JavaScript.
   *
   * Cached so the BeforeStep hook does not re-probe the driver on every
   * step once it has been determined.
   */
  protected bool $bigPipeJsIsSupported = FALSE;

  /**
   * Whether to skip the per-step BigPipe cookie reset.
   *
   * Defaults to TRUE so scenarios where BigPipe handling is irrelevant
   * (skip tag, JS driver, opt-out config) do not pay any cost. The
   * BeforeScenario hook flips this to FALSE when the cookie should be
   * kept alive across user logins / cookie-clearing redirects.
   */
  protected bool $bigPipeSkipBeforeStep = TRUE;

  /**
   * Sets the BigPipe NOJS cookie if the driver does not support JavaScript.
   */
  #[BeforeScenario]
  public function bigPipeBeforeScenario(BeforeScenarioScope $scope): void {
    // Reset state in case the context instance is reused across scenarios.
    $this->bigPipeSkipBeforeStep = TRUE;

    if ($scope->getScenario()->hasTag('behat-steps-skip:bigPipeBeforeScenario')) {
      return;
    }

    if (!$this->bigPipeBypassEnabled()) {
      return;
    }

    $this->bigPipeSkipBeforeStep = $scope->getScenario()->hasTag('behat-steps-skip:bigPipeBeforeStep');

    $this->bigPipeApplyCookie();
  }

  /**
   * Re-applies the BigPipe NOJS cookie if it was cleared during the scenario.
   *
   * Logging in or out, and certain redirects, drop session cookies. Without
   * this hook a cookie set in BeforeScenario disappears the first time the
   * test user changes - and any subsequent authenticated-user assertion
   * fails the same way the original issue describes.
   */
  #[BeforeStep]
  public function bigPipeBeforeStep(): void {
    if ($this->bigPipeSkipBeforeStep) {
      return;
    }

    $this->bigPipeApplyCookie();
  }

  /**
   * Sets the BigPipe NOJS cookie when applicable.
   *
   * Returns silently when BigPipe is not bootstrapped, when JavaScript is
   * supported, or when the cookie is already present. Any
   * 'DriverException' raised by the underlying Mink driver (Selenium throws
   * before the browser is open) is caught - the next BeforeStep retry will
   * succeed once the driver is started.
   */
  protected function bigPipeApplyCookie(): void {
    if (!$this->bigPipeIsAvailable()) {
      return;
    }

    try {
      $this->bigPipeJsIsSupported = $this->bigPipeIsJavascriptSupported();

      if ($this->bigPipeJsIsSupported) {
        return;
      }

      if ($this->getSession()->getCookie(self::BIG_PIPE_NOJS_COOKIE) === NULL) {
        $this->getSession()->setCookie(self::BIG_PIPE_NOJS_COOKIE, 'true');
      }
    }
    catch (DriverException) {
      // Driver session is not ready - the next BeforeStep retry will set
      // the cookie once the driver is started.
    }
  }

  /**
   * Whether BigPipe bypass is enabled via the extension option.
   *
   * Defaults to TRUE so the fix is opt-out, not opt-in. Projects that
   * need to keep streaming behaviour for their own assertions can set
   * 'big_pipe.bypass: false' in 'behat.yml'.
   */
  protected function bigPipeBypassEnabled(): bool {
    $config = $this->getDrupalParameter('big_pipe');

    if (!is_array($config) || !array_key_exists('bypass', $config)) {
      return TRUE;
    }

    return (bool) $config['bypass'];
  }

  /**
   * Whether the BigPipe module is available in the bootstrapped Drupal kernel.
   *
   * Returns FALSE for non-API drivers (Drush, Blackbox) where
   * '\Drupal::hasService()' cannot be called - the cookie is harmless in
   * that case but the no-op behaviour is explicit so sites without
   * BigPipe never see an unexpected cookie.
   */
  protected function bigPipeIsAvailable(): bool {
    if (!class_exists(\Drupal::class, FALSE)) {
      return FALSE;
    }

    try {
      return \Drupal::hasService('big_pipe');
    }
    catch (\Throwable) {
      return FALSE;
    }
  }

  /**
   * Whether the active Mink driver can execute JavaScript.
   *
   * Probes the driver with 'evaluateScript()' rather than relying on a tag
   * convention; this matches what BigPipe itself checks at runtime and works
   * for any Mink driver, including custom ones. The driver is intentionally
   * not auto-started: hooks fire before any scenario step and other Mink
   * extensions rely on the session staying dormant until the first page
   * visit. An unstarted driver is treated as non-JS - the per-step
   * BeforeStep hook re-probes once the driver is started.
   */
  protected function bigPipeIsJavascriptSupported(): bool {
    try {
      $driver = $this->getSession()->getDriver();

      if (!$driver->isStarted()) {
        return FALSE;
      }

      $driver->evaluateScript('true');

      return TRUE;
    }
    catch (UnsupportedDriverActionException | \Exception) {
      return FALSE;
    }
  }

}
