<?php

declare(strict_types=1);

namespace Drupal\behat_test\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\Response;

/**
 * Controller that displays all message types for testing MessageContext.
 */
class MessagesController extends ControllerBase {

  /**
   * Renders a page that displays error, warning, and status messages.
   *
   * Returns a plain HTML response with message markup matching the Drupal
   * message selectors, bypassing caching and lazy builders.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   An HTML response.
   */
  public function page(): Response {
    $html = <<<'HTML'
<html>
<head><title>Test Messages</title></head>
<body>
<div id="main">
<div class="messages messages--error">
  <ul>
    <li>Test error message.</li>
    <li>Another error message.</li>
  </ul>
</div>
<div class="messages messages--status">
  <ul>
    <li>Test status message.</li>
  </ul>
</div>
<div class="messages messages--warning">
  <ul>
    <li>Test warning message.</li>
    <li>Another warning message.</li>
  </ul>
</div>
<p style="color: green;">This page displays test messages.</p>
</div>
</body>
</html>
HTML;

    return new Response($html);
  }

}
