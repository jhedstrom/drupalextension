<?php

declare(strict_types=1);

namespace Drupal\DrupalExtension\Context\Traits;

use Behat\Hook\BeforeScenario;
use Behat\Hook\BeforeStep;
use Behat\Mink\Exception\DriverException;

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
 * Opt-in: tag the scenario or feature with '@bigpipe'. Without that tag
 * the hooks are no-ops, so projects that do not exercise BigPipe pay no
 * cost.
 *
 * Used in the standard 'DrupalContext'. The host class is expected to
 * extend 'RawDrupalContext' so '$this->getSession()' is available.
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
   * Whether '@bigpipe' is active for the current scenario.
   *
   * Set in 'bigPipeBeforeScenario' (which Behat only fires for tagged
   * scenarios) and reset in 'bigPipeResetActivation' (fired for every
   * scenario). The Behat 3 'BeforeStep' attribute does not support tag
   * filters, so this flag gates the per-step cookie reset instead.
   */
  protected bool $bigPipeIsActive = FALSE;

  /**
   * Whether the active driver supports JavaScript.
   *
   * Cached so the BeforeStep hook does not re-probe the driver on every
   * step once it has been determined.
   */
  protected bool $bigPipeJsIsSupported = FALSE;

  /**
   * Resets the activation flag at the start of every scenario.
   *
   * Behat reuses context instances across scenarios in a suite, so the
   * '@bigpipe' flag must be cleared between runs - otherwise a tagged
   * scenario would leave the bypass on for every subsequent scenario in
   * the same suite.
   */
  #[BeforeScenario]
  public function bigPipeResetActivation(): void {
    $this->bigPipeIsActive = FALSE;
    $this->bigPipeJsIsSupported = FALSE;
  }

  /**
   * Activates BigPipe handling and applies the cookie when '@bigpipe' is set.
   *
   * The Behat 'BeforeScenario' attribute filter applies the hook only to
   * scenarios tagged '@bigpipe' (feature-level tags inherit to scenarios,
   * matching the convention used by '@api', '@javascript', and '@mail').
   */
  #[BeforeScenario('@bigpipe')]
  public function bigPipeActivate(): void {
    $this->bigPipeIsActive = TRUE;
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
    if (!$this->bigPipeIsActive) {
      return;
    }

    $this->bigPipeApplyCookie();
  }

  /**
   * Sets the BigPipe NOJS cookie when the driver does not support JavaScript.
   *
   * Returns silently when JavaScript is supported. Any 'DriverException'
   * raised by the underlying Mink driver (Selenium throws before the
   * browser is open) is caught - the next BeforeStep retry will succeed
   * once the driver is started.
   *
   * 'setCookie()' is idempotent on the cookie jar, so re-applying the
   * same value on every BeforeStep is harmless. The cookie value is
   * intentionally not read first - BrowserKit's 'getCookie()' calls
   * 'getCurrentUrl()' which throws when no page has been visited yet,
   * suppressing the subsequent 'setCookie()' call.
   */
  protected function bigPipeApplyCookie(): void {
    try {
      $this->bigPipeJsIsSupported = $this->bigPipeIsJavascriptSupported();

      if ($this->bigPipeJsIsSupported) {
        return;
      }

      $this->getSession()->setCookie(self::BIG_PIPE_NOJS_COOKIE, 'true');
    }
    catch (DriverException) {
      // Driver session is not ready - the next BeforeStep retry will set
      // the cookie once the driver is started.
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
    catch (DriverException) {
      return FALSE;
    }
  }

}
