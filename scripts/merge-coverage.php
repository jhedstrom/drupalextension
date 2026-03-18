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
 *
 * @phpcs:disable PSR1.Files.SideEffects.FoundWithSymbols
 */

declare(strict_types=1);

use SebastianBergmann\CodeCoverage\CodeCoverage;
use SebastianBergmann\CodeCoverage\Report\Cobertura;
use SebastianBergmann\CodeCoverage\Report\Html\Facade;
use SebastianBergmann\CodeCoverage\Report\Text;
use SebastianBergmann\CodeCoverage\Report\Thresholds;

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

/**
 * Merged coverage object accumulating all sources.
 *
 * @var \SebastianBergmann\CodeCoverage\CodeCoverage|null $merged
 */
$merged = NULL;
$mergeCount = 0;

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
  $mergeCount++;
  echo sprintf("  \033[2m◆ Loaded %s coverage from: %s\033[0m%s", $label, $file, PHP_EOL);
}

// Find and merge subprocess coverage files.
$subprocessFiles = [];
if (is_dir(SOURCE_SUBPROCESS_COVERAGE_DIR)) {
  $subprocessFiles = glob(SOURCE_SUBPROCESS_COVERAGE_DIR . '/*.php');
}

if (!empty($subprocessFiles)) {
  echo sprintf("  \033[2m◆ Merging %d subprocess coverage files...\033[0m%s", count($subprocessFiles), PHP_EOL);

  foreach ($subprocessFiles as $file) {
    try {
      $subprocessCoverage = include $file;
      if ($subprocessCoverage instanceof CodeCoverage) {
        if ($merged === NULL) {
          $merged = $subprocessCoverage;
        }
        else {
          $merged->merge($subprocessCoverage);
        }
        $mergeCount++;
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

echo sprintf('▶ Merged %d coverage sources.%s', $mergeCount, PHP_EOL);

// Save merged coverage.
$outputDir = dirname(OUTPUT_MERGED_COVERAGE_FILE);
if (!is_dir($outputDir)) {
  mkdir($outputDir, 0755, TRUE);
}

file_put_contents(OUTPUT_MERGED_COVERAGE_FILE, '<?php' . PHP_EOL . 'return \\unserialize(' . var_export(serialize($merged), TRUE) . ');' . PHP_EOL);
echo sprintf("  \033[2m✦ Coverage merged and saved to: %s\033[0m%s", OUTPUT_MERGED_COVERAGE_FILE, PHP_EOL);

// Generate Cobertura report.
$coberturaReport = new Cobertura();
file_put_contents(OUTPUT_COBERTURA_REPORT_FILE, $coberturaReport->process($merged));
echo sprintf("  \033[2m✦ Cobertura report generated: %s\033[0m%s", OUTPUT_COBERTURA_REPORT_FILE, PHP_EOL);

// Generate HTML report.
$htmlReport = new Facade();
$htmlReport->process($merged, OUTPUT_HTML_REPORT_DIR);
echo sprintf("  \033[2m✦ HTML report generated: %s\033[0m%s", OUTPUT_HTML_REPORT_DIR, PHP_EOL);

// Generate text report and split into summary and details.
$textReport = new Text(Thresholds::default(), TRUE);
$coverageText = $textReport->process($merged, FALSE);
file_put_contents(COVERAGE_ROOT_PATH . '/merged/coverage.txt', $coverageText);

$lines = explode("\n", $coverageText);
$summaryLines = [];
$detailsLines = [];
$inDetails = FALSE;
foreach ($lines as $line) {
  if (!$inDetails && preg_match('/^\S+\\\\\S+/', $line)) {
    $inDetails = TRUE;
  }
  if ($inDetails) {
    $detailsLines[] = $line;
  }
  elseif (preg_match('/^\s+(Classes|Methods|Lines):/', $line, $matches)) {
    $summaryLines[$matches[1]] = $line;
  }
}
$summaryOrder = ['Lines', 'Methods', 'Classes'];
$orderedSummary = array_filter(array_map(fn(string $key): ?string => $summaryLines[$key] ?? NULL, $summaryOrder));
file_put_contents(COVERAGE_ROOT_PATH . '/merged/coverage-summary.txt', implode("\n", $orderedSummary) . "\n");
file_put_contents(COVERAGE_ROOT_PATH . '/merged/coverage-details.txt', implode("\n", $detailsLines));
echo sprintf("  \033[2m✦ Text report generated: %s/merged/coverage.txt\033[0m%s", COVERAGE_ROOT_PATH, PHP_EOL);

echo "\033[32mCoverage merge finished\033[0m" . PHP_EOL;
echo '  Report: .logs/coverage/merged/.coverage-html/index.html' . PHP_EOL;
