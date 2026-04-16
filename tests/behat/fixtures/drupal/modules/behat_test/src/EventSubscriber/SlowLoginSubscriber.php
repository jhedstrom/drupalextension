<?php

declare(strict_types=1);

namespace Drupal\behat_test\EventSubscriber;

use Drupal\Core\State\StateInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Simulates a slow post-login page by delaying the logged-in body class.
 *
 * When 'behat_test.slow_login' state is set to a positive integer (ms),
 * strips the 'logged-in' class from the body tag and injects JavaScript
 * that re-adds it after the configured delay. This reproduces the race
 * condition where JS or async processing delays the logged-in indicator.
 *
 * Requires a JS-capable driver (Selenium) to observe the effect.
 */
class SlowLoginSubscriber implements EventSubscriberInterface {

  public function __construct(
    protected StateInterface $state,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    return [KernelEvents::RESPONSE => ['onResponse', -100]];
  }

  /**
   * Removes the logged-in class and re-adds it after a delay.
   */
  public function onResponse(ResponseEvent $event): void {
    $delay = (int) $this->state->get('behat_test.slow_login', 0);
    if ($delay <= 0) {
      return;
    }

    $response = $event->getResponse();
    $content = $response->getContent();
    if ($content === FALSE) {
      return;
    }

    // Only act on pages where the logged-in class is present.
    if (!str_contains($content, 'logged-in')) {
      return;
    }

    // Remove the logged-in class from the body tag.
    $content = preg_replace('/(<body[^>]*class="[^"]*)\blogged-in\b/', '$1', $content);

    // Inject JS to re-add it after the configured delay.
    $js = sprintf(
      '<script>setTimeout(function(){document.body.classList.add("logged-in");}, %d);</script>',
      $delay
    );
    $content = str_replace('</body>', $js . '</body>', $content);

    $response->setContent($content);
  }

}
