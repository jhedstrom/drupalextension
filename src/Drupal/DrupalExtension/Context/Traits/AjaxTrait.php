<?php

declare(strict_types=1);

namespace Drupal\DrupalExtension\Context\Traits;

use Behat\Behat\Hook\Scope\AfterStepScope;
use Behat\Behat\Hook\Scope\BeforeStepScope;
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
      var d7_not_ajaxing = true;
      if (typeof Drupal !== 'undefined' && typeof Drupal.ajax !== 'undefined' && typeof Drupal.ajax.instances === 'undefined') {
        for(var i in Drupal.ajax) { if (isAjaxing(Drupal.ajax[i])) { d7_not_ajaxing = false; } }
      }
      var d8_not_ajaxing = (typeof Drupal === 'undefined' || typeof Drupal.ajax === 'undefined' || typeof Drupal.ajax.instances === 'undefined' || !Drupal.ajax.instances.some(isAjaxing))
      return (
        // Assert no AJAX request is running (via jQuery or Drupal) and no
        // animation is running.
        (typeof jQuery === 'undefined' || jQuery.hasOwnProperty('active') === false || (jQuery.active <= 0 && jQuery(':animated').length === 0)) &&
        d7_not_ajaxing && d8_not_ajaxing
      );
    }());
JS;
    $ajax_timeout = $this->getParameter('ajax_timeout');
    $result = $this->getSession()->wait(1000 * $ajax_timeout, $condition);

    if (!$result) {
      if ($ajax_timeout === NULL) {
        throw new \RuntimeException('No AJAX timeout has been defined. Please verify that "Drupal\DrupalExtension" is configured in behat.yml.');
      }

      if ($event) {
        /** @var \Behat\Behat\Hook\Scope\BeforeStepScope $event */
        $event_data = ' ' . json_encode([
          'name' => $event->getName(),
          'feature' => $event->getFeature()->getTitle(),
          'step' => $event->getStep()->getText(),
          'suite' => $event->getSuite()->getName(),
        ]);
      }
      else {
        $event_data = '';
      }

      throw new \RuntimeException('Unable to complete AJAX request.' . $event_data);
    }
  }

}
