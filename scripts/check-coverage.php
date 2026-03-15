<?php

/**
 * @file
 * Check code coverage for a specific class or context.
 *
 * Usage:
 * php check-coverage.php <ClassName> [coverage_file_path]
 *
 * Where:
 * - ClassName: The name of the class to check (e.g., "DrupalContext")
 * - coverage_file_path: Optional path to the cobertura.xml file.
 *   Defaults to '/var/www/html/.logs/coverage/merged/cobertura.xml'.
 *
 * Examples:
 * php check-coverage.php DrupalContext
 * php check-coverage.php MinkContext .logs/coverage/behat/cobertura.xml
 */

declare(strict_types=1);

if (empty($argv[1])) {
  echo "Error: Class name is required.\n\n";
  echo "Usage: php check-coverage.php <ClassName> [coverage_file_path]\n";
  echo "Example: php check-coverage.php DrupalContext\n";
  exit(1);
}

$traitName = $argv[1];
$defaultCoverageFile = file_exists('/var/www/html/.logs/coverage/merged/cobertura.xml')
  ? '/var/www/html/.logs/coverage/merged/cobertura.xml'
  : __DIR__ . '/../.logs/coverage/merged/cobertura.xml';
$coverageFile = $argv[2] ?? $defaultCoverageFile;

if (!file_exists($coverageFile)) {
  echo sprintf("Error: Coverage file not found: %s\n", $coverageFile);
  exit(1);
}

$xml = simplexml_load_file($coverageFile);
if ($xml === FALSE) {
  echo sprintf("Error: Failed to parse coverage file: %s\n", $coverageFile);
  exit(1);
}

$xml->registerXPathNamespace('c', 'http://cobertura.sourceforge.net/xml/coverage-04.dtd');

$classes = $xml->xpath(sprintf('//class[contains(@name, "%s")]', $traitName));

if (empty($classes)) {
  echo sprintf("Error: Class '%s' not found in coverage report.\n", $traitName);
  exit(1);
}

foreach ($classes as $class) {
  $className = (string) $class['name'];
  $lineRate = (float) $class['line-rate'];
  $percentage = number_format($lineRate * 100, 2);

  echo sprintf("Class: %s\n", $className);
  echo sprintf("Line rate: %s (%s%%)\n\n", $lineRate, $percentage);

  $uncovered = [];
  if (property_exists($class->lines, 'line') && $class->lines->line !== NULL) {
    foreach ($class->lines->line as $line) {
      if ((string) $line['hits'] === '0') {
        $uncovered[] = (string) $line['number'];
      }
    }
  }

  echo "Uncovered lines:\n";
  if (!empty($uncovered)) {
    echo implode(', ', $uncovered) . "\n";
  }
  else {
    echo "None (100% coverage)\n";
  }

  echo "\n";
}

exit(0);
