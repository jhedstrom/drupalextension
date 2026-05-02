<?php

declare(strict_types=1);

namespace Drupal\behat_test\EventSubscriber;

use Drupal\Core\State\StateInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Simulates slow post-login DOM signals.
 *
 * Two independent state keys control which signal is delayed:
 *
 * - 'behat_test.slow_login' (ms): strips the 'logged-in' class from the
 *   body tag and re-adds it via JS after the configured delay.
 * - 'behat_test.slow_logout_link' (ms): strips logout links from the
 *   response and re-injects one via JS after the configured delay.
 *
 * Both manipulations rely on a JS-capable driver (Selenium) to observe the
 * delayed re-addition.
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
   * Strips logged-in indicators and schedules their delayed re-addition.
   */
  public function onResponse(ResponseEvent $event): void {
    $login_delay = (int) $this->state->get('behat_test.slow_login', 0);
    $logout_link_delay = (int) $this->state->get('behat_test.slow_logout_link', 0);

    if ($login_delay <= 0 && $logout_link_delay <= 0) {
      return;
    }

    $response = $event->getResponse();
    $content = $response->getContent();
    if ($content === FALSE) {
      return;
    }

    $scripts = '';

    if ($login_delay > 0 && str_contains($content, 'logged-in')) {
      $content = preg_replace('/(<body[^>]*class="[^"]*)\blogged-in\b/', '$1', $content);
      $scripts .= sprintf(
        '<script>setTimeout(function(){document.body.classList.add("logged-in");}, %d);</script>',
        $login_delay
      );
    }

    if ($logout_link_delay > 0) {
      $count = 0;
      $stripped = preg_replace('/<a\b[^>]*href="[^"]*\/user\/logout[^"]*"[^>]*>.*?<\/a>/is', '', $content, -1, $count);
      if ($count > 0) {
        $content = $stripped;
        $scripts .= sprintf(
          '<script>setTimeout(function(){var a=document.createElement("a");a.href="/user/logout";a.textContent="Log out";document.body.appendChild(a);}, %d);</script>',
          $logout_link_delay
        );
      }
    }

    if ($scripts !== '') {
      $content = str_replace('</body>', $scripts . '</body>', $content);
      $response->setContent($content);
    }
  }

}
