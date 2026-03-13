<?php

/**
 * @file
 * Merge coverage files from subprocess Behat runs into the main coverage file.
 *
 * Usage:
 * php merge-coverage.php [coverage_root_path].
 *
 * Where coverage_root_path is the optional path to the coverage root directory.
 * Defaults to '/var/www/html/.logs/coverage'.
 *
 * This will also generate Cobertura and HTML reports from the merged coverage
 * data.
 */

declare(strict_types=1);

use SebastianBergmann\CodeCoverage\CodeCoverage;
use SebastianBergmann\CodeCoverage\Report\Cobertura;
use SebastianBergmann\CodeCoverage\Report\Html\Facade;

require_once __DIR__ . '/../vendor/autoload.php';

// Get coverage root path from command line argument or use default.
define('COVERAGE_ROOT_PATH', $argv[1] ?? '/var/www/html/.logs/coverage');

// Source coverage files to be merged.
define('SOURCE_MAIN_COVERAGE_FILE', COVERAGE_ROOT_PATH . '/behat/phpcov.php');
define('SOURCE_SUBPROCESS_COVERAGE_DIR', COVERAGE_ROOT_PATH . '/behat_cli/phpcov');

// Output files for merged coverage and reports.
define('OUTPUT_MERGED_COVERAGE_FILE', COVERAGE_ROOT_PATH . '/behat_merged/phpcov.php');
define('OUTPUT_COBERTURA_REPORT_FILE', COVERAGE_ROOT_PATH . '/behat_merged/cobertura.xml');
define('OUTPUT_HTML_REPORT_DIR', COVERAGE_ROOT_PATH . '/behat_merged/.coverage-html');

if (!file_exists(SOURCE_MAIN_COVERAGE_FILE)) {
  echo sprintf('Main coverage file not found: %s%s', SOURCE_MAIN_COVERAGE_FILE, PHP_EOL);
  exit(1);
}

// Load main coverage.
try {
  $main_coverage = @include SOURCE_MAIN_COVERAGE_FILE;
}
catch (\Throwable $e) {
  echo "Error loading main Behat coverage file: " . $e->getMessage() . "\n";
  echo "Skipping coverage merge.\n";
  exit(0);
}

if (!$main_coverage instanceof CodeCoverage) {
  echo "Invalid main Behat coverage file format or unserialization failed.\n";
  echo "This may be due to version incompatibility. Skipping coverage merge.\n";
  exit(0);
}

// Find all subprocess coverage files.
$subprocess_files = [];
if (is_dir(SOURCE_SUBPROCESS_COVERAGE_DIR)) {
  $subprocess_files = glob(SOURCE_SUBPROCESS_COVERAGE_DIR . '/*.php');
}

if (empty($subprocess_files)) {
  echo "No subprocess coverage files found, skipping merge.\n";
  exit(0);
}

echo "Merging " . count($subprocess_files) . " subprocess coverage files...\n";

foreach ($subprocess_files as $file) {
  try {
    $subprocess_coverage = include $file;
    if ($subprocess_coverage instanceof CodeCoverage) {
      $main_coverage->merge($subprocess_coverage);
      echo "  Merged: " . basename($file) . "\n";
    }
  }
  catch (\Exception $e) {
    echo "  Error merging " . basename($file) . ": " . $e->getMessage() . "\n";
  }
}

// Save merged coverage.
$output_dir = dirname(OUTPUT_MERGED_COVERAGE_FILE);
if (!is_dir($output_dir)) {
  mkdir($output_dir, 0755, TRUE);
}

file_put_contents(OUTPUT_MERGED_COVERAGE_FILE, '<?php' . PHP_EOL . 'return \\unserialize(' . var_export(serialize($main_coverage), TRUE) . ');' . PHP_EOL);

echo sprintf('Coverage merged and saved to: %s%s', OUTPUT_MERGED_COVERAGE_FILE, PHP_EOL);

// Generate Cobertura report.
$cobertura_report = new Cobertura();
file_put_contents(OUTPUT_COBERTURA_REPORT_FILE, $cobertura_report->process($main_coverage));
echo sprintf('Cobertura report generated: %s%s', OUTPUT_COBERTURA_REPORT_FILE, PHP_EOL);

// Generate HTML report.
$html_report = new Facade();
$html_report->process($main_coverage, OUTPUT_HTML_REPORT_DIR);
echo sprintf('HTML report generated: %s%s', OUTPUT_HTML_REPORT_DIR, PHP_EOL);
