<?php

/**
 * @file
 * Blackbox fixture that enforces HTTP Basic authentication.
 *
 * Served by the blackbox PHP server. Responds with 401 until credentials
 * arrive, then echoes the authenticated username - so a scenario can prove
 * that basic auth is applied and survives session resets.
 */

declare(strict_types=1);

$user = $_SERVER['PHP_AUTH_USER'] ?? NULL;

// PHP's built-in server does not always split the Authorization header into
// PHP_AUTH_USER / PHP_AUTH_PW, so parse it directly when needed.
if (($user === NULL || $user === '') && isset($_SERVER['HTTP_AUTHORIZATION']) && str_starts_with((string) $_SERVER['HTTP_AUTHORIZATION'], 'Basic ')) {
  $decoded = base64_decode(substr((string) $_SERVER['HTTP_AUTHORIZATION'], 6), TRUE);
  if ($decoded !== FALSE && str_contains($decoded, ':')) {
    [$user] = explode(':', $decoded, 2);
  }
}

if ($user === NULL || $user === '') {
  header('WWW-Authenticate: Basic realm="Behat"');
  http_response_code(401);
  echo 'Unauthorized';
}
else {
  echo '<html><body><h1>Authenticated as ' . htmlspecialchars($user) . '</h1></body></html>';
}
