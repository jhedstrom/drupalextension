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

$trait_name = $argv[1];
$default_coverage_file = file_exists('/var/www/html/.logs/coverage/merged/cobertura.xml')
  ? '/var/www/html/.logs/coverage/merged/cobertura.xml'
  : __DIR__ . '/../.logs/coverage/merged/cobertura.xml';
$coverage_file = $argv[2] ?? $default_coverage_file;

if (!file_exists($coverage_file)) {
    echo sprintf("Error: Coverage file not found: %s\n", $coverage_file);
    exit(1);
}

$xml = simplexml_load_file($coverage_file);
if ($xml === false) {
    echo sprintf("Error: Failed to parse coverage file: %s\n", $coverage_file);
    exit(1);
}

$xml->registerXPathNamespace('c', 'http://cobertura.sourceforge.net/xml/coverage-04.dtd');

$classes = $xml->xpath(sprintf('//class[contains(@name, "%s")]', $trait_name));

if (empty($classes)) {
    echo sprintf("Error: Class '%s' not found in coverage report.\n", $trait_name);
    exit(1);
}

foreach ($classes as $class) {
    $class_name = (string) $class['name'];
    $line_rate = (float) $class['line-rate'];
    $percentage = number_format($line_rate * 100, 2);

    echo sprintf("Class: %s\n", $class_name);
    echo sprintf("Line rate: %s (%s%%)\n\n", $line_rate, $percentage);

    $uncovered = [];
    if (property_exists($class->lines, 'line') && $class->lines->line !== null) {
        foreach ($class->lines->line as $line) {
            if ((string) $line['hits'] === '0') {
                $uncovered[] = (string) $line['number'];
            }
        }
    }

    echo "Uncovered lines:\n";
    if (!empty($uncovered)) {
        echo implode(', ', $uncovered) . "\n";
    } else {
        echo "None (100% coverage)\n";
    }

    echo "\n";
}

exit(0);
