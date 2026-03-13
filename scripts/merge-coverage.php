<?php

/**
 * @file
 * Merge coverage files from PHPUnit, Behat, and subprocess Behat runs.
 *
 * Usage:
 * php merge-coverage.php [coverage_root_path].
 *
 * Where coverage_root_path is the optional path to the coverage root directory.
 * Defaults to '/var/www/html/.logs/coverage'.
 *
 * This will merge coverage from:
 * - PHPUnit: {root}/phpunit/phpcov.php
 * - Behat main: {root}/behat/phpcov.php
 * - Behat subprocess: {root}/behat_cli/phpcov/*.php
 *
 * And generate Cobertura and HTML reports in {root}/merged/.
 */

declare(strict_types=1);

use SebastianBergmann\CodeCoverage\CodeCoverage;
use SebastianBergmann\CodeCoverage\Report\Cobertura;
use SebastianBergmann\CodeCoverage\Report\Html\Facade;

require_once __DIR__ . '/../vendor/autoload.php';

// Get coverage root path from command line argument or use default.
define('COVERAGE_ROOT_PATH', $argv[1] ?? '/var/www/html/.logs/coverage');

// Source coverage files to be merged.
$sources = [
  'PHPUnit' => COVERAGE_ROOT_PATH . '/phpunit/phpcov.php',
  'Behat' => COVERAGE_ROOT_PATH . '/behat/phpcov.php',
];

// Behat subprocess coverage directory.
define('SOURCE_SUBPROCESS_COVERAGE_DIR', COVERAGE_ROOT_PATH . '/behat_cli/phpcov');

// Output files for merged coverage and reports.
define('OUTPUT_MERGED_COVERAGE_FILE', COVERAGE_ROOT_PATH . '/merged/phpcov.php');
define('OUTPUT_COBERTURA_REPORT_FILE', COVERAGE_ROOT_PATH . '/merged/cobertura.xml');
define('OUTPUT_HTML_REPORT_DIR', COVERAGE_ROOT_PATH . '/merged/.coverage-html');

echo "\033[32mCoverage merge started\033[0m" . PHP_EOL;

/** @var \SebastianBergmann\CodeCoverage\CodeCoverage|null $merged */
$merged = NULL;
$merge_count = 0;

// Load main coverage sources (PHPUnit and Behat).
foreach ($sources as $label => $file) {
  if (!file_exists($file)) {
    echo sprintf("  \033[2m◇ %s coverage not found (skipping)\033[0m%s", $label, PHP_EOL);
    continue;
  }

  try {
    $coverage = @include $file;
  }
  catch (\Throwable $e) {
    echo sprintf('  ⚠ Error loading %s coverage: %s (skipping)%s', $label, $e->getMessage(), PHP_EOL);
    continue;
  }

  if (!$coverage instanceof CodeCoverage) {
    echo sprintf('  ⚠ Invalid %s coverage file format (skipping)%s', $label, PHP_EOL);
    continue;
  }

  if ($merged === NULL) {
    $merged = $coverage;
  }
  else {
    $merged->merge($coverage);
  }
  $merge_count++;
  echo sprintf("  \033[2m◆ Loaded %s coverage from: %s\033[0m%s", $label, $file, PHP_EOL);
}

// Find and merge subprocess coverage files.
$subprocess_files = [];
if (is_dir(SOURCE_SUBPROCESS_COVERAGE_DIR)) {
  $subprocess_files = glob(SOURCE_SUBPROCESS_COVERAGE_DIR . '/*.php');
}

if (!empty($subprocess_files)) {
  echo sprintf("  \033[2m◆ Merging %d subprocess coverage files...\033[0m%s", count($subprocess_files), PHP_EOL);

  foreach ($subprocess_files as $file) {
    try {
      $subprocess_coverage = include $file;
      if ($subprocess_coverage instanceof CodeCoverage) {
        if ($merged === NULL) {
          $merged = $subprocess_coverage;
        }
        else {
          $merged->merge($subprocess_coverage);
        }
        $merge_count++;
        echo "\033[2m    ├ Merged: " . basename($file) . "\033[0m" . PHP_EOL;
      }
    }
    catch (\Exception $e) {
      echo '    ⚠ Error merging ' . basename($file) . ': ' . $e->getMessage() . PHP_EOL;
    }
  }
}

if ($merged === NULL) {
  echo '  ⚠ No coverage files found, nothing to merge.' . PHP_EOL;
  exit(1);
}

echo sprintf('▶ Merged %d coverage sources.%s', $merge_count, PHP_EOL);

// Save merged coverage.
$output_dir = dirname(OUTPUT_MERGED_COVERAGE_FILE);
if (!is_dir($output_dir)) {
  mkdir($output_dir, 0755, TRUE);
}

file_put_contents(OUTPUT_MERGED_COVERAGE_FILE, '<?php' . PHP_EOL . 'return \\unserialize(' . var_export(serialize($merged), TRUE) . ');' . PHP_EOL);
echo sprintf("  \033[2m✦ Coverage merged and saved to: %s\033[0m%s", OUTPUT_MERGED_COVERAGE_FILE, PHP_EOL);

// Generate Cobertura report.
$cobertura_report = new Cobertura();
file_put_contents(OUTPUT_COBERTURA_REPORT_FILE, $cobertura_report->process($merged));
echo sprintf("  \033[2m✦ Cobertura report generated: %s\033[0m%s", OUTPUT_COBERTURA_REPORT_FILE, PHP_EOL);

// Generate HTML report.
$html_report = new Facade();
$html_report->process($merged, OUTPUT_HTML_REPORT_DIR);
echo sprintf("  \033[2m✦ HTML report generated: %s\033[0m%s", OUTPUT_HTML_REPORT_DIR, PHP_EOL);


echo "\033[32mCoverage merge finished\033[0m" . PHP_EOL;
echo '  Report: .logs/coverage/merged/.coverage-html/index.html' . PHP_EOL;
