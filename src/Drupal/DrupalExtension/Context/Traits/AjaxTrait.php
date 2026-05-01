<?php

declare(strict_types=1);

namespace Drupal\DrupalExtension\Context\Traits;

use Behat\Behat\Hook\Scope\AfterStepScope;
use Behat\Behat\Hook\Scope\BeforeStepScope;
use Behat\Behat\Hook\Scope\StepScope;
use Behat\Hook\AfterStep;
use Behat\Hook\BeforeStep;
use Behat\Step\Given;
use Drupal\DrupalExtension\ParametersTrait;
use Drupal\DrupalExtension\TagTrait;

/**
 * Waits for Drupal-aware AJAX requests to complete on JavaScript-driven steps.
 *
 * Mink's vanilla AJAX wait checks 'jQuery.active' only. Drupal renders many
 * page updates through 'Drupal.ajax' / 'Drupal.ajax.instances' which are not
 * tracked by 'jQuery.active', so a 'Then I should see ...' assertion fired
 * immediately after a 'When I press ...' step would race the AJAX completion
 * and intermittently fail. The JS condition embedded here probes Drupal's own
 * 'ajaxing' flag to wait for the *Drupal* request, not just any jQuery one.
 */
trait AjaxTrait {

  use ParametersTrait;
  use TagTrait;

  /**
   * For javascript enabled scenarios, always wait for AJAX before clicking.
   */
  #[BeforeStep]
  public function beforeJavascriptStep(BeforeStepScope $event): void {
    // Make sure the feature is registered in case this hook fires before
    // ::registerFeature() which is also a @BeforeStep. Behat doesn't
    // support ordering hooks.
    $this->registerFeature($event);

    if (!$this->hasTag('javascript')) {
      return;
    }

    $text = $event->getStep()->getText();
    if (preg_match('/\b(follow|press|click|submit|attach)\b/i', $text)) {
      $this->iWaitForAjaxToFinish($event);
    }
  }

  /**
   * For javascript enabled scenarios, always wait for AJAX after clicking.
   */
  #[AfterStep]
  public function afterJavascriptStep(AfterStepScope $event): void {
    if (!$this->hasTag('javascript')) {
      return;
    }

    $text = $event->getStep()->getText();
    if (preg_match('/\b(follow|press|click|submit|attach)\b/i', $text)) {
      $this->iWaitForAjaxToFinish($event);
    }
  }

  /**
   * Wait for AJAX to finish.
   *
   * @see \Drupal\FunctionalJavascriptTests\JSWebAssert::assertWaitOnAjaxRequest()
   *
   * @code
   * Given I wait for AJAX to finish
   * @endcode
   */
  #[Given('I wait for AJAX to finish')]
  public function iWaitForAjaxToFinish(mixed $event = NULL): void {
    if (!$this->getSession()->isStarted()) {
      return;
    }

    $condition = <<<JS
    (function() {
      function isAjaxing(instance) {
        return instance && instance.ajaxing === true;
      }
      var drupal_not_ajaxing = (typeof Drupal === 'undefined' || typeof Drupal.ajax === 'undefined' || typeof Drupal.ajax.instances === 'undefined' || !Drupal.ajax.instances.some(isAjaxing));
      return (
        // Assert no AJAX request is running (via jQuery or Drupal) and no
        // animation is running.
        (typeof jQuery === 'undefined' || jQuery.hasOwnProperty('active') === false || (jQuery.active <= 0 && jQuery(':animated').length === 0)) &&
        drupal_not_ajaxing
      );
    }());
JS;
    $ajax_timeout = $this->getParameter('ajax_timeout');
    $result = $this->getSession()->wait(1000 * $ajax_timeout, $condition);

    if (!$result) {
      if ($ajax_timeout === NULL) {
        throw new \RuntimeException('No AJAX timeout has been defined. Please verify that "Drupal\DrupalExtension" is configured in behat.yml.');
      }

      $diagnostics = $this->captureAjaxDiagnostics();
      $event_data = $event instanceof StepScope ? [
        'hook' => $event->getName(),
        'feature' => $event->getFeature()->getTitle(),
        'step' => $event->getStep()->getText(),
        'suite' => $event->getSuite()->getName(),
      ] : NULL;

      throw new \RuntimeException(self::formatTimeoutMessage((int) $ajax_timeout, $diagnostics, $event_data));
    }
  }

  /**
   * Probes the browser for diagnostic state when an AJAX wait times out.
   *
   * Runs after 'wait()' has already returned false, so the cost of an extra
   * round-trip only applies on the failure path. Returning NULL is fine - the
   * caller falls back to the event-only message.
   *
   * @return array<string, mixed>|null
   *   Decoded diagnostic data, or NULL if the driver cannot evaluate JS or
   *   the probe returned malformed output.
   */
  protected function captureAjaxDiagnostics(): ?array {
    $script = <<<JS_WRAP
    (function() {
      function collectInstances() {
        var out = [];
        if (typeof Drupal === 'undefined' || typeof Drupal.ajax === 'undefined' || typeof Drupal.ajax.instances === 'undefined') {
          return out;
        }
        Drupal.ajax.instances.forEach(function(instance, idx) {
          if (!instance || instance.ajaxing !== true) {
            return;
          }
          var info = { index: idx };
          if (instance.url) {
            info.url = instance.url;
          }
          if (instance.element_settings) {
            if (instance.element_settings.selector) {
              info.selector = instance.element_settings.selector;
            }
            if (instance.element_settings.event) {
              info.event = instance.element_settings.event;
            }
          }
          out.push(info);
        });
        return out;
      }
      return JSON.stringify({
        url: window.location.href,
        jquery_active: (typeof jQuery !== 'undefined' && typeof jQuery.active !== 'undefined') ? jQuery.active : null,
        jquery_animated: (typeof jQuery !== 'undefined') ? jQuery(':animated').length : null,
        drupal_ajax_instances: collectInstances()
      });
    }())
    JS_WRAP;

    try {
      $raw = $this->getSession()->evaluateScript($script);
    }
    catch (\Throwable) {
      return NULL;
    }

    if (is_string($raw)) {
      $decoded = json_decode($raw, TRUE);

      return is_array($decoded) ? $decoded : NULL;
    }

    return is_array($raw) ? $raw : NULL;
  }

  /**
   * Formats a verbose timeout exception message.
   *
   * Pure helper - no instance state - so the formatting can be unit tested
   * with synthetic input rather than a live browser session.
   *
   * @param int $ajax_timeout
   *   The configured timeout in seconds.
   * @param array<string, mixed>|null $diagnostics
   *   Browser state captured at time of failure, or NULL when unavailable.
   * @param array<string, string>|null $event_data
   *   Step scope data when called from a hook, otherwise NULL. Expected keys:
   *   'hook', 'feature', 'step', 'suite'.
   */
  protected static function formatTimeoutMessage(int $ajax_timeout, ?array $diagnostics, ?array $event_data): string {
    $lines = [sprintf('Unable to complete AJAX request after %d second(s).', $ajax_timeout)];

    if ($diagnostics !== NULL) {
      $lines[] = '';

      if (isset($diagnostics['url']) && is_string($diagnostics['url'])) {
        $lines[] = 'URL: ' . $diagnostics['url'];
      }

      $lines[] = 'jQuery active requests: ' . self::formatScalar($diagnostics['jquery_active'] ?? NULL);
      $lines[] = 'jQuery animated elements: ' . self::formatScalar($diagnostics['jquery_animated'] ?? NULL);

      $instances = is_array($diagnostics['drupal_ajax_instances'] ?? NULL) ? $diagnostics['drupal_ajax_instances'] : [];
      $lines[] = sprintf('Drupal AJAX instances active: %d', count($instances));

      foreach ($instances as $instance) {
        if (!is_array($instance)) {
          continue;
        }

        $parts = [];
        foreach (['selector', 'event', 'url', 'index'] as $field) {
          if (isset($instance[$field])) {
            $parts[] = sprintf("%s: '%s'", $field, $instance[$field]);
          }
        }
        $lines[] = '  - ' . (count($parts) > 0 ? implode(', ', $parts) : '(no details)');
      }
    }

    if ($event_data !== NULL) {
      $lines[] = '';
      $lines[] = 'Hook: ' . ($event_data['hook'] ?? '');
      $lines[] = 'Feature: ' . ($event_data['feature'] ?? '');
      $lines[] = 'Step: ' . ($event_data['step'] ?? '');
      $lines[] = 'Suite: ' . ($event_data['suite'] ?? '');
    }

    return implode("\n", $lines);
  }

  /**
   * Renders a scalar diagnostic value for the message, NULL = 'unavailable'.
   */
  private static function formatScalar(mixed $value): string {
    if ($value === NULL) {
      return 'unavailable';
    }

    if (is_bool($value)) {
      return $value ? 'true' : 'false';
    }

    return (string) $value;
  }

}
