<?php

declare(strict_types=1);

namespace Drupal\behat_test\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\Response;

/**
 * Controller that renders a table with buttons for testing assertPressInTableRow.
 */
class TableButtonController extends ControllerBase {

  /**
   * Renders a page with a table containing buttons in rows.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   An HTML response.
   */
  public function page(): Response {
    $html = <<<'HTML'
<html>
<head><title>Table Button Test</title></head>
<body>
<form method="post">
<table>
  <thead>
    <tr><th>Title</th><th>Operations</th></tr>
  </thead>
  <tbody>
    <tr><td>First row</td><td><input type="submit" value="Edit" /></td></tr>
    <tr><td>Second row</td><td><input type="submit" value="Delete" /></td></tr>
  </tbody>
</table>
</form>
</body>
</html>
HTML;

    return new Response($html);
  }

}
