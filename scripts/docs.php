<?php

/**
 * @file
 * Documentation generator.
 *
 * This script generates the documentation for the steps in the Behat
 * features.
 *
 * It parses the docblock comments of the Context classes and methods in the
 * src/Drupal/DrupalExtension/Context directory and generates STEPS.md file.
 *
 * It also validates the steps and checks if they are in the correct
 * format.
 *
 * Run with --fail-on-change to fail if the documentation is not up to date.
 * Run with --path=path/to/dir to specify a custom path for the output file.
 * Run with --warning-on-invalid to report validation errors as warnings
 * instead of failing.
 * Run with --log-dir=path to write validation-summary.txt and
 * validation-details.txt (plain text, no ANSI) to the specified directory.
 *
 * Step definition conventions (to be enforced in version 6):
 * - @Given steps ending with ':' must contain the word "following".
 * - @When steps must contain "I " (first person).
 * - @Then steps must contain the word "should".
 * - @Then steps must contain "the", "a", or "no".
 * - @Then method names must contain "Assert".
 * - @Then method names must NOT contain "Should".
 * - All steps must have an @code/@endcode example in the docblock.
 * - Each method should define only one step annotation.
 * - Steps should use turnip syntax instead of unnecessary regex.
 *
 * @phpcs:disable PSR1.Files.SideEffects.FoundWithSymbols
 */

declare(strict_types=1);

// Execute the main function only when the script is run directly, not when included.
// @codeCoverageIgnoreStart
if (basename((string) $_SERVER['SCRIPT_FILENAME']) === 'docs.php') {
  $options = getopt('', ['fail-on-change', 'path::', 'warning-on-invalid', 'log-dir::']);
  main($options);
}
// @codeCoverageIgnoreEnd

/**
 * Main function to handle the documentation generation process.
 *
 * @param array<string, bool|string|array<int, string>> $options
 *   Command line options.
 *
 * @codeCoverageIgnoreStart
 */
function main(array $options = []): void {
  $basePath = is_string($options['path'] ?? NULL) ? $options['path'] : dirname(__DIR__);

  require_once $basePath . '/vendor/autoload.php';

  $contextDir = $basePath . '/src/Drupal/DrupalExtension/Context';
  $info = extract_info($contextDir, [], $basePath);

  $lenient = isset($options['warning-on-invalid']);
  $results = validate($info, $basePath);

  $treeOutput = '';
  if (has_validation_errors($results)) {
    $treeOutput = render_validation_tree($results);
    echo $treeOutput;
    if (!$lenient) {
      exit(1);
    }
  }

  $logDir = is_string($options['log-dir'] ?? NULL) ? $options['log-dir'] : NULL;
  if ($logDir !== NULL) {
    write_validation_logs($treeOutput, $logDir);
  }

  $stepsMarkdown = PHP_EOL . render_info($info, $basePath) . PHP_EOL;
  $readmeMarkdown = PHP_EOL . render_info($info, $basePath, 'STEPS.md') . PHP_EOL;

  $stepsFile = 'STEPS.md';
  $stepsContents = file_get_contents($basePath . DIRECTORY_SEPARATOR . $stepsFile);
  if ($stepsContents === FALSE) {
    printf('Failed to read %s.' . PHP_EOL, $stepsFile);
    exit(1);
  }
  $stepsReplaced = replace_content($stepsContents, '# Available steps', '[//]: # (END)', $stepsMarkdown);

  $readmeFile = 'README.md';
  $readmeContents = file_get_contents($basePath . DIRECTORY_SEPARATOR . $readmeFile);
  if ($readmeContents === FALSE) {
    printf('Failed to read %s.' . PHP_EOL, $readmeFile);
    exit(1);
  }
  $readmeReplaced = replace_content($readmeContents, '## Available steps', '[//]: # (END)', $readmeMarkdown);

  if ($stepsReplaced === $stepsContents && $readmeReplaced === $readmeContents) {
    echo PHP_EOL . "\033[32mDocumentation is up to date. No changes were made.\033[0m" . PHP_EOL;
    exit(0);
  }

  $failOnChange = isset($options['fail-on-change']);
  if ($failOnChange && ($stepsReplaced !== $stepsContents || $readmeReplaced !== $readmeContents)) {
    echo PHP_EOL . "\033[31mDocumentation is outdated. Please regenerate documentation.\033[0m" . PHP_EOL;
    exit(1);
  }
  file_put_contents($basePath . DIRECTORY_SEPARATOR . $stepsFile, $stepsReplaced);
  file_put_contents($basePath . DIRECTORY_SEPARATOR . $readmeFile, $readmeReplaced);
  echo 'Documentation updated.' . PHP_EOL;
}

// @codeCoverageIgnoreEnd

/**
 * Parse info from the Context classes in a directory.
 *
 * @param string $contextDir
 *   The directory containing Context classes.
 * @param array<int, string> $exclude
 *   Array of class names to exclude.
 * @param string $basePath
 *   Base path for the repository.
 *
 * @return array<string,array<string, array<int, array<string, array<int,string>|string>>|string>>
 *   Array of info with 'name', 'steps', 'description', and 'example' keys.
 *
 * @throws \ReflectionException
 */
function extract_info(string $contextDir, array $exclude = [], string $basePath = '', string $namespace = 'Drupal\\DrupalExtension\\Context'): array {
  if (empty($basePath)) {
    // @codeCoverageIgnore
    $basePath = dirname(__DIR__);
  }

  $info = [];

  if (!is_dir($contextDir)) {
    throw new \Exception(sprintf('Context directory %s does not exist', $contextDir));
  }

  // Collect all PHP files in the context directory.
  $files = scandir($contextDir) ?: [];
  $classFiles = [];
  foreach ($files as $file) {
    if (is_file($contextDir . DIRECTORY_SEPARATOR . $file) && str_ends_with($file, '.php')) {
      $classFiles[] = basename($file, '.php');
    }
  }
  sort($classFiles);

  foreach ($classFiles as $className) {
    if (in_array($className, $exclude, TRUE)) {
      continue;
    }

    $fqcn = $namespace . '\\' . $className;

    if (!class_exists($fqcn)) {
      continue;
    }

    $reflection = new ReflectionClass($fqcn);
    // Skip interfaces and abstract classes.
    // @codeCoverageIgnoreStart
    if ($reflection->isInterface()) {
      continue;
    }
    // @codeCoverageIgnoreEnd
    if ($reflection->isAbstract()) {
      continue;
    }

    $classInfo = [
      'name' => $className,
      'name_contextual' => $className,
      'context' => $className,
      'methods' => [],
    ];
    $classInfo += parse_class_comment($className, (string) $reflection->getDocComment());

    // Get all public methods declared in this class (not inherited).
    $methods = $reflection->getMethods(ReflectionMethod::IS_PUBLIC);
    foreach ($methods as $method) {
      // Only include methods declared in this class.
      if ($method->getDeclaringClass()->getName() !== $fqcn) {
        continue;
      }

      $parsedComment = parse_method_comment((string) $method->getDocComment());
      if ($parsedComment) {
        $classInfo['methods'][] = $parsedComment + ['name' => $method->getName()];
      }
    }

    if (!empty($classInfo['methods'])) {
      // Sort info by Given, When, Then.
      usort($classInfo['methods'], static function (array $a, array $b): int {
          $order = ['@Given', '@When', '@Then'];

          $getOrderIndex = function ($step) use ($order): int {
            foreach ($order as $index => $prefix) {
              if (str_starts_with($step, $prefix)) {
                return $index;
              }
            }

              // @codeCoverageIgnoreStart
              return PHP_INT_MAX;
              // @codeCoverageIgnoreEnd
          };

          $aStep = $a['steps'][0] ?? '';
          $bStep = $b['steps'][0] ?? '';

          $aIndex = $getOrderIndex($aStep);
          $bIndex = $getOrderIndex($bStep);

          return $aIndex <=> $bIndex;
      });
    }

    // Only include classes that have step definitions.
    if (!empty($classInfo['methods'])) {
      $info[$className] = $classInfo;
    }
  }

  return $info;
}

/**
 * Parse class comment.
 *
 * @param string $className
 *   The class name.
 * @param string $comment
 *   The comment.
 *
 * @return array<string, string>
 *   Array of 'description' and 'description_full' keys.
 */
function parse_class_comment(string $className, string $comment): array {
  if (empty($comment)) {
    throw new \Exception(sprintf('Class comment for %s is empty', $className));
  }

  $comment = preg_replace('#^/\*\*|^\s*\*\/$#m', '', $comment);
  $lines = explode(PHP_EOL, (string) $comment);
  // Remove docblock asterisk and up to one space, but preserve remaining indentation.
  $lines = array_map(static fn(string $l): string => preg_replace('/^\s*\* ?/', '', $l), $lines);

  // Remove first and last empty lines.
  if (count($lines) > 1 && empty($lines[0])) {
    array_shift($lines);
  }
  if (count($lines) > 1 && empty($lines[count($lines) - 1])) {
    array_pop($lines);
  }

  // Trim lines, but preserve indentation within @code blocks.
  $inCodeBlock = FALSE;
  $lines = array_map(static function (string $l) use (&$inCodeBlock): string {
    if (str_starts_with(trim($l), '@code')) {
        $inCodeBlock = TRUE;
        return trim($l);
    }
    if (str_starts_with(trim($l), '@endcode')) {
        $inCodeBlock = FALSE;
        return trim($l);
    }
    if ($inCodeBlock) {
        // Preserve indentation within code blocks.
        return rtrim($l);
    }
      return trim($l);
  }, $lines);

  // @codeCoverageIgnoreStart
  if (empty($lines)) {
    throw new \Exception(sprintf('Class comment for %s is empty', $className));
  }
  // @codeCoverageIgnoreEnd
  $description = $lines[0];
  if (empty($description)) {
    throw new \Exception(sprintf('Class comment for %s is empty', $className));
  }

  if (str_starts_with($description, 'Class ')) {
    throw new \Exception(sprintf('Class comment should have a descriptive content for %s', $className));
  }

  $fullDescription = implode(PHP_EOL, $lines);

  if (substr_count($fullDescription, '`') % 2 !== 0) {
    throw new \Exception(sprintf('Class inline code block is not closed for %s', $className));
  }

  return [
    'description' => $description,
    'description_full' => $fullDescription,
  ];
}

/**
 * Parse comment.
 *
 * @param string $comment
 *   The comment.
 *
 * @return array<string, array<int, string>|string>|null
 *   Array of 'steps', 'description', and 'example' keys or NULL if steps were
 *   not found in the comment.
 */
function parse_method_comment(string $comment): ?array {
  if (empty($comment)) {
    return NULL;
  }

  $return = [
    'steps' => [],
    'description' => '',
    'example' => '',
  ];

  $lines = explode(PHP_EOL, $comment);

  $exampleStart = FALSE;
  foreach ($lines as $line) {
    $line = str_replace('/*', '', $line);
    $line = str_replace('/**', '', $line);
    $line = str_replace('*/', '', $line);
    $line = preg_replace('/^\s*\*/', '', $line);
    $line = rtrim((string) $line, " \t\n\r\0\x0B");
    // All docblock lines start with a space.
    $line = substr($line, 1);

    if (str_starts_with($line, '@code')) {
      $exampleStart = TRUE;
    }
    elseif (str_starts_with($line, '@endcode')) {
      $exampleStart = FALSE;
    }
    elseif (str_starts_with($line, '@Given') || str_starts_with($line, '@When') || str_starts_with($line, '@Then')) {
      $line = trim($line, " \t\n\r\0\x0B");
      $return['steps'][] = $line;
    }
    else {
      if (!$exampleStart && empty($line)) {
        continue;
      }

      if ($exampleStart) {
        $line = rtrim($line, "\t\n\r\0\x0B");
        $return['example'] .= $line . PHP_EOL;
      }

      if (empty($return['description'])) {
        $line = trim($line);
        $return['description'] .= $line . ' ';
      }
    }
  }

  if ($exampleStart) {
    throw new \Exception('Example not closed');
  }

  if (!empty($return['steps'])) {
    // Sort the steps by Given, When, Then.
    $sorted = [];
    foreach (['@Given', '@When', '@Then'] as $step) {
      foreach ($return['steps'] as $stepItem) {
        if (str_starts_with($stepItem, $step)) {
          $sorted[] = $stepItem;
        }
      }
    }
    $return['steps'] = $sorted;

    $return['description'] = trim($return['description']);

    if (!empty($return['example'])) {
      // Remove indentation from the example, using the first line as a
      // reference.
      $lines = explode(PHP_EOL, $return['example']);
      $firstLine = '';
      foreach ($lines as $l) {
        if ($l !== '') {
          $firstLine = $l;
          break;
        }
      }
      $indentation = strspn($firstLine, ' ');
      foreach ($lines as $key => $line) {
        $line = rtrim($line);
        if (strlen($line) > $indentation) {
          $lines[$key] = substr($line, $indentation);
        }
      }
      $return['example'] = implode(PHP_EOL, $lines);
    }
  }

  return empty($return['steps']) ? NULL : $return;
}

/**
 * Convert info to content.
 *
 * @param array<string,array<string, array<int, array<string, array<int,string>|string>>|string>> $info
 *   Array of info items with 'name', 'from', and 'to' keys.
 * @param string $basePath
 *   Base path for the repository.
 * @param string|null $pathForLinks
 *   Path prefix for links in the index (e.g. 'STEPS.md').
 *
 * @return string
 *   Markdown table.
 */
function render_info(array $info, string $basePath = '', ?string $pathForLinks = NULL): string {
  if (empty($basePath)) {
    // @codeCoverageIgnore
    $basePath = dirname(__DIR__);
  }

  $contentOutput = '';
  $indexRows = [];

  foreach ($info as $class => $classInfo) {
    // Find the source file.
    $srcFile = find_source_file($class, $basePath);
    if (!$srcFile) {
      throw new \Exception(sprintf('Source file for %s does not exist', $class));
    }

    // Find the example feature file.
    $exampleName = camel_to_snake(str_replace('Context', '', $class));
    $exampleFile = sprintf('tests/behat/features/%s.feature', $exampleName);
    $exampleFilePath = $basePath . DIRECTORY_SEPARATOR . $exampleFile;

    $exampleLink = '';
    if (file_exists($exampleFilePath)) {
      $exampleLink = sprintf(', [Example](%s)', $exampleFile);
    }

    $contentOutput .= sprintf('## %s', $classInfo['name_contextual']) . PHP_EOL . PHP_EOL;
    $contentOutput .= sprintf('[Source](%s)%s', $srcFile, $exampleLink) . PHP_EOL . PHP_EOL;

    // Add description as markdown-safe accommodating for lists.
    $descriptionFull = '';
    $lines = explode(PHP_EOL, $classInfo['description_full']);
    $wasList = FALSE;
    $inCodeBlock = FALSE;
    $codeBlock = '';
    foreach ($lines as $line) {
      $trimmedLine = trim($line);

      // Handle @code tag - start collecting code block.
      if (str_starts_with($trimmedLine, '@code')) {
        $inCodeBlock = TRUE;
        $codeBlock = '';
        continue;
      }

      // Handle @endcode tag - wrap collected code in markdown code block.
      if (str_starts_with($trimmedLine, '@endcode')) {
        $inCodeBlock = FALSE;
        $descriptionFull .= '```' . PHP_EOL;
        $descriptionFull .= rtrim($codeBlock) . PHP_EOL;
        $descriptionFull .= '```' . PHP_EOL;
        $codeBlock = '';
        continue;
      }

      // If inside code block, collect lines without processing.
      if ($inCodeBlock) {
        $codeBlock .= $line . PHP_EOL;
        continue;
      }

      $isList = str_starts_with($trimmedLine, '-');

      if (!$isList) {
        if (empty($line) && !$wasList) {
          $descriptionFull .= $line . '<br/><br/>' . PHP_EOL;
        }
        else {
          $descriptionFull .= $line . PHP_EOL;
        }
        $wasList = FALSE;
      }
      else {
        if (str_ends_with($descriptionFull, '<br/><br/>' . PHP_EOL)) {
          $descriptionFull = rtrim($descriptionFull, '<br/><br/>' . PHP_EOL) . PHP_EOL;
        }

        $descriptionFull .= $line . PHP_EOL;
        $wasList = TRUE;
      }
    }

    $descriptionFull = preg_replace('/^/m', '>  ', $descriptionFull);
    $contentOutput .= $descriptionFull . PHP_EOL . PHP_EOL;

    // Add to index.
    $indexRowsPath = '#' . preg_replace('/[^A-Za-z0-9_\-]/', '', strtolower((string) $classInfo['name_contextual']));
    if ($pathForLinks) {
      $indexRowsPath = $pathForLinks . $indexRowsPath;
    }
    $indexRows[] = [
      sprintf('[%s](%s)', $classInfo['name_contextual'], $indexRowsPath),
      $classInfo['description'],
    ];

    foreach ($classInfo['methods'] as $method) {
      $method['steps'] = is_array($method['steps']) ? $method['steps'] : [$method['steps']];
      $method['description'] = is_string($method['description']) ? $method['description'] : '';
      $method['example'] = is_string($method['example']) ? $method['example'] : '';

      $method['steps'] = array_reduce($method['steps'], fn(string $carry, string $item): string => $carry . sprintf("%s\n", $item), '');
      $method['steps'] = rtrim((string) $method['steps'], "\n");

      $method['description'] = rtrim((string) $method['description'], '.');

      $template = <<<EOT
<details>
  <summary><code>[step]</code></summary>

<br/>
[description]
<br/><br/>

```gherkin
[example]
```

</details>

EOT;

      $contentOutput .= strtr(
            $template,
            [
              '[description]' => $method['description'],
              '[step]' => $method['steps'],
              '[example]' => $method['example'],
            ]
        );

      $contentOutput .= PHP_EOL;
    }
  }

  $indexOutput = '';
  if (!empty($indexRows)) {
    $indexOutput .= array_to_markdown_table(['Class', 'Description'], $indexRows) . PHP_EOL . PHP_EOL;
  }

  $output = '';
  $output .= $indexOutput . PHP_EOL;

  // Render content if this is not a path for links.
  if (!$pathForLinks) {
    $output .= '---' . PHP_EOL . PHP_EOL;
    $output .= $contentOutput . PHP_EOL;
  }

  return $output;
}

/**
 * Find the source file for a class relative to the base path.
 *
 * @param string $className
 *   The class name.
 * @param string $basePath
 *   Base path for the repository.
 *
 * @return string|null
 *   The relative path to the source file, or NULL if not found.
 */
function find_source_file(string $className, string $basePath): ?string {
  $srcFile = sprintf('src/Drupal/DrupalExtension/Context/%s.php', $className);
  $srcFilePath = $basePath . DIRECTORY_SEPARATOR . $srcFile;

  if (file_exists($srcFilePath)) {
    return $srcFile;
  }

  // Fallback: try root src directory (for tests).
  $srcFile = sprintf('src/%s.php', $className);
  $srcFilePath = $basePath . DIRECTORY_SEPARATOR . $srcFile;

  if (file_exists($srcFilePath)) {
    return $srcFile;
  }

  return NULL;
}

/**
 * Validate the info.
 *
 * @param array<string,array<string, array<int, array<string, array<int,string>|string>>|string>> $info
 *   Array of info items with 'name', 'from', and 'to' keys.
 * @param string $basePath
 *   Base path for the repository.
 *
 * @return array<string, array<string, array<string, bool|string|array<string, array<string, bool|array<int, string>>>>>>
 *   Structured validation results per class and method.
 */
function validate(array $info, string $basePath = ''): array {
  if (empty($basePath)) {
    // @codeCoverageIgnore
    $basePath = dirname(__DIR__);
  }

  $results = [];

  foreach ($info as $classInfo) {
    $className = is_string($classInfo['name']) ? $classInfo['name'] : '';

    // Check example file.
    $exampleName = camel_to_snake(str_replace('Context', '', $className));
    $exampleFile = sprintf('tests/behat/features/%s.feature', $exampleName);
    $exampleFilePath = $basePath . DIRECTORY_SEPARATOR . $exampleFile;

    $classResult = [
      'file' => [
        'pass' => file_exists($exampleFilePath),
        'path' => $exampleFile,
      ],
      'methods' => [],
    ];

    foreach ($classInfo['methods'] as $method) {
      $method['steps'] = is_array($method['steps']) ? $method['steps'] : [$method['steps']];
      $method['name'] = is_string($method['name']) ? $method['name'] : '';
      $method['description'] = is_string($method['description']) ? $method['description'] : '';
      $method['example'] = is_string($method['example']) ? $method['example'] : '';

      $step = (string) $method['steps'][0];

      // Step wording check.
      $stepWording = ['pass' => TRUE, 'messages' => []];
      if (str_starts_with($step, '@Given') && str_ends_with($step, ':') && !str_contains($step, 'following')) {
        $stepWording['pass'] = FALSE;
        $stepWording['messages'][] = 'Missing "following" in the step';
      }
      if (str_starts_with($step, '@When') && !str_contains($step, 'I ')) {
        $stepWording['pass'] = FALSE;
        $stepWording['messages'][] = 'Missing "I " in the step';
      }
      if (str_starts_with($step, '@Then')) {
        if (!str_contains($step, ' should ')) {
          $stepWording['pass'] = FALSE;
          $stepWording['messages'][] = 'Missing "should" in the step';
        }
        if (!(str_contains($step, ' the ') || str_contains($step, ' a ') || str_contains($step, ' no '))) {
          $stepWording['pass'] = FALSE;
          $stepWording['messages'][] = 'Missing "the", "a" or "no" in the step';
        }
      }

      // Method naming check.
      $methodNaming = ['pass' => TRUE, 'messages' => []];
      if (str_starts_with($step, '@Then')) {
        if (!str_contains((string) $method['name'], 'Assert')) {
          $methodNaming['pass'] = FALSE;
          $methodNaming['messages'][] = 'Missing "Assert" in the method name';
        }
        if (str_contains((string) $method['name'], 'Should')) {
          $methodNaming['pass'] = FALSE;
          $methodNaming['messages'][] = 'Contains "Should" in the method name';
        }
      }

      // Single step check.
      $singleStep = ['pass' => TRUE, 'messages' => []];
      if (count($method['steps']) > 1) {
        $singleStep['pass'] = FALSE;
        $singleStep['messages'][] = 'Multiple steps found';
      }

      // Has example check.
      $hasExample = ['pass' => TRUE, 'messages' => []];
      if (empty($method['example'])) {
        $hasExample['pass'] = FALSE;
        $hasExample['messages'][] = 'Missing example';
      }

      // Unnecessary regex check.
      $regexConvertible = ['pass' => TRUE, 'messages' => []];
      $suggested = regex_to_turnip($step);
      if ($suggested !== NULL) {
        $regexConvertible['pass'] = FALSE;
        $regexConvertible['messages'][] = $step;
        $regexConvertible['messages'][] = $suggested;
      }

      $classResult['methods'][$method['name']] = [
        'step_wording' => $stepWording,
        'method_naming' => $methodNaming,
        'single_step' => $singleStep,
        'has_example' => $hasExample,
        'regex_convertible' => $regexConvertible,
      ];
    }

    $results[$className] = $classResult;
  }

  return $results;
}

/**
 * Check if validation results contain any errors.
 *
 * @param array<string, array<string, array<string, bool|string|array<string, array<string, bool|array<int, string>>>>>> $results
 *   Structured validation results from validate().
 *
 * @return bool
 *   TRUE if there are validation errors.
 */
function has_validation_errors(array $results): bool {
  foreach ($results as $classResult) {
    foreach ($classResult['methods'] as $methodChecks) {
      foreach ($methodChecks as $check) {
        if (!$check['pass']) {
          return TRUE;
        }
      }
    }
  }

  return FALSE;
}

/**
 * Render validation results as a tree with ANSI colors.
 *
 * @param array<string, array<string, array<string, bool|string|array<string, array<string, bool|array<int, string>>>>>> $results
 *   Structured validation results from validate().
 *
 * @return string
 *   The rendered tree output.
 */
function render_validation_tree(array $results): string {
  $bold = "\033[1m";
  $green = "\033[32m";
  $yellow = "\033[33m";
  $dim = "\033[2m";
  $reset = "\033[0m";

  $symbols = [
    'step_wording' => ['pass' => '◆', 'warn' => '◇', 'label' => 'Step wording'],
    'method_naming' => ['pass' => '▲', 'warn' => '△', 'label' => 'Method naming'],
    'single_step' => ['pass' => '●', 'warn' => '○', 'label' => 'Single step'],
    'has_example' => ['pass' => '✦', 'warn' => '✧', 'label' => 'Example'],
    'regex_convertible' => ['pass' => '⬢', 'warn' => '⬡', 'label' => 'Regex usage'],
  ];

  // Count totals and violations per category.
  $totalClasses = count($results);
  $totalMethods = 0;
  $totalViolations = 0;
  $counts = [
    'step_wording' => 0,
    'method_naming' => 0,
    'single_step' => 0,
    'has_example' => 0,
    'regex_convertible' => 0,
    'file' => 0,
  ];
  foreach ($results as $classResult) {
    if (!$classResult['file']['pass']) {
      $counts['file']++;
      $totalViolations++;
    }
    foreach ($classResult['methods'] as $methodChecks) {
      $totalMethods++;
      foreach (array_keys($symbols) as $key) {
        if (!$methodChecks[$key]['pass']) {
          $counts[$key]++;
          $totalViolations++;
        }
      }
    }
  }

  $output = '';
  $output .= $yellow . 'Validation warnings:' . $reset . PHP_EOL . PHP_EOL;

  $classNames = array_keys($results);

  foreach ($classNames as $className) {
    $classResult = $results[$className];
    $output .= $bold . $className . $reset . PHP_EOL;

    $methodNames = array_keys($classResult['methods']);
    $totalChildren = 1 + count($methodNames);
    $childIndex = 0;

    // File check.
    $childIndex++;
    $isLast = ($childIndex === $totalChildren);
    $branch = $isLast ? '└── ' : '├── ';

    $fileCont = $isLast ? '    ' : '│   ';
    if ($classResult['file']['pass']) {
      $output .= '  ' . $branch . $green . '■' . $reset . ' Example file present' . PHP_EOL;
    }
    else {
      $output .= '  ' . $branch . $yellow . '□' . $reset . ' Example file absent' . PHP_EOL;
      $output .= '  ' . $fileCont . '  ' . $dim . $classResult['file']['path'] . $reset . PHP_EOL;
    }

    // Methods.
    foreach ($methodNames as $methodName) {
      $childIndex++;
      $isLastMethod = ($childIndex === $totalChildren);
      $methodBranch = $isLastMethod ? '└── ' : '├── ';
      $methodCont = $isLastMethod ? '    ' : '│   ';

      $output .= '  ' . $methodBranch . $methodName . PHP_EOL;

      $checks = $classResult['methods'][$methodName];
      $checkKeys = array_keys($symbols);
      $totalChecks = count($checkKeys);

      foreach ($checkKeys as $ki => $checkKey) {
        $check = $checks[$checkKey];
        $sym = $symbols[$checkKey];
        $isLastCheck = ($ki === $totalChecks - 1);
        $checkBranch = $isLastCheck ? '└── ' : '├── ';
        $checkCont = $isLastCheck ? '      ' : '│     ';

        if ($check['pass']) {
          $output .= '  ' . $methodCont . $checkBranch . $green . $sym['pass'] . $reset . ' ' . $sym['label'] . PHP_EOL;
        }
        else {
          $output .= '  ' . $methodCont . $checkBranch . $yellow . $sym['warn'] . $reset . ' ' . $sym['label'] . PHP_EOL;
          foreach ($check['messages'] as $message) {
            $output .= '  ' . $methodCont . $checkCont . $dim . $message . $reset . PHP_EOL;
          }
        }
      }
    }

    $output .= PHP_EOL;
  }

  // Summary.
  $output .= $yellow . 'Summary:' . $reset . PHP_EOL;
  $output .= '  Scanned ' . $totalClasses . ' classes, ' . $totalMethods . ' steps.' . PHP_EOL . PHP_EOL;

  $summaryLines = [
    [
      'Step wording', $symbols['step_wording'],
      $totalMethods - $counts['step_wording'], $counts['step_wording'], $totalMethods,
    ],
    [
      'Method naming', $symbols['method_naming'],
      $totalMethods - $counts['method_naming'], $counts['method_naming'], $totalMethods,
    ],
    [
      'Single step', $symbols['single_step'],
      $totalMethods - $counts['single_step'], $counts['single_step'], $totalMethods,
    ],
    [
      'Example', $symbols['has_example'],
      $totalMethods - $counts['has_example'], $counts['has_example'], $totalMethods,
    ],
    [
      'Regex usage', $symbols['regex_convertible'],
      $totalMethods - $counts['regex_convertible'], $counts['regex_convertible'], $totalMethods,
    ],
    [
      'Example file', ['pass' => '■', 'warn' => '□'],
      $totalClasses - $counts['file'], $counts['file'], $totalClasses,
    ],
  ];

  foreach ($summaryLines as [$label, $sym, $passCount, $failCount, $total]) {
    $lineColor = $failCount > 0 ? $yellow : $green;
    $check = $failCount === 0 ? '✓' : '⚠';
    $passStr = str_pad((string) $passCount, 3, ' ', STR_PAD_LEFT);
    $failStr = str_pad((string) $failCount, 3, ' ', STR_PAD_LEFT);
    $output .= $lineColor . '  ' . sprintf('%-15s', $label) . $sym['pass'] . $passStr . '  ' . $sym['warn'] . $failStr . '  ' . $check . $reset . PHP_EOL;
  }

  return $output;
}

/**
 * Write validation logs to a directory as plain text files.
 *
 * Writes two files:
 * - validation-summary.txt: The summary block.
 * - validation-details.txt: The per-context tree.
 *
 * @param string $treeOutput
 *   The rendered validation tree (with ANSI codes).
 * @param string $logDir
 *   The directory to write the log files to.
 */
function write_validation_logs(string $treeOutput, string $logDir): void {
  if (!is_dir($logDir)) {
    mkdir($logDir, 0777, TRUE);
  }

  // Strip ANSI escape codes.
  $plain = (string) preg_replace('/\033\[[0-9;]*m/', '', $treeOutput);

  if (empty(trim($plain))) {
    file_put_contents($logDir . '/validation-summary.txt', 'No validation warnings.' . PHP_EOL);
    file_put_contents($logDir . '/validation-details.txt', '');
    return;
  }

  // Split at "Summary:" line.
  $parts = explode('Summary:', $plain, 2);
  $details = trim($parts[0]);
  $summary = isset($parts[1]) ? 'Summary:' . $parts[1] : $details;

  file_put_contents($logDir . '/validation-summary.txt', $summary);
  file_put_contents($logDir . '/validation-details.txt', $details . PHP_EOL);
}

/**
 * Convert a regex step definition to turnip syntax if possible.
 *
 * Returns the turnip equivalent if the regex is unnecessary, or NULL if the
 * regex uses features that cannot be expressed in turnip syntax.
 *
 * @param string $step
 *   The step definition string (e.g. '@Given /^I visit "([^"]*)"$/').
 *
 * @return string|null
 *   The turnip equivalent (e.g. '@Given I visit :arg1'), or NULL.
 */
function regex_to_turnip(string $step): ?string {
  // Extract annotation and pattern.
  if (!preg_match('#^(@(?:Given|When|Then))\s+/\^(.*)\$/$#', $step, $matches)) {
    return NULL;
  }

  $annotation = $matches[1];
  $pattern = $matches[2];

  // Check for regex features that cannot be expressed in turnip syntax.
  // Alternation (|), optional groups (?), lookahead/lookbehind, quantifiers
  // on non-capture-group content, character classes other than [^"]*.
  // We only convert if the pattern is literal text with simple capture groups.
  // Replace all capture groups with a placeholder to check the rest.
  $argCount = 0;
  $converted = preg_replace_callback('#\(([^)]*)\)#', function (array $m) use (&$argCount): string {
      $inner = $m[1];
      // Simple quoted string capture: [^"]*  or [^']*.
    if ($inner === '[^"]*' || $inner === "[^']*") {
        $argCount++;
        return ':arg' . $argCount;
    }
      // Simple unquoted capture: .*  or .+.
    if ($inner === '.*' || $inner === '.+') {
        $argCount++;
        return ':arg' . $argCount;
    }
      // Numeric capture: \d+ or [0-9]+.
    if ($inner === '\d+' || $inner === '[0-9]+') {
        $argCount++;
        return ':arg' . $argCount;
    }
      // Word capture: \w+.
    if ($inner === '\w+') {
        $argCount++;
        return ':arg' . $argCount;
    }

      // Return original match to signal unconvertible pattern.
      return $m[0];
  }, $pattern);

  // If any capture group could not be converted, bail.
  if ($converted === NULL || str_contains($converted, '(')) {
    return NULL;
  }

  // Check remaining literal text for regex metacharacters.
  // Remove known-safe escaped characters first.
  $literalCheck = preg_replace('#\\\\[/"\'.]#', '', $converted);
  // If there are remaining backslash sequences or regex metacharacters, bail.
  if ($literalCheck !== NULL && preg_match('#[\\\\.*+?\[\]{}|^$]#', $literalCheck)) {
    return NULL;
  }

  // Unescape safe characters in the converted pattern.
  $converted = str_replace(['\\/', '\\"', "\\'", '\\.'], ['/', '"', "'", '.'], $converted);

  // Remove surrounding quotes from capture group contexts.
  // e.g., ":arg1" becomes :arg1 (Behat turnip handles quoting automatically).
  $converted = preg_replace('#"(:arg\d+)"#', '$1', $converted);
  $converted = preg_replace("#'(:arg\d+)'#", '$1', (string) $converted);

  return $annotation . ' ' . $converted;
}

/**
 * Convert a string to snake case.
 *
 * @param string $string
 *   The string to convert.
 * @param string $separator
 *   The separator.
 *
 * @return string
 *   The converted string.
 */
function camel_to_snake(string $string, string $separator = '_'): string {
  $string = preg_replace_callback('/([^0-9])(\d+)/', static fn(array $matches): string => $matches[1] . $separator . $matches[2], $string);

  $replacements = [];
  foreach (mb_str_split((string) $string) as $key => $char) {
    $lowerCaseChar = mb_strtolower($char);
    if ($lowerCaseChar !== $char && $key !== 0) {
      $replacements[$char] = $separator . $char;
    }
  }
  $string = str_replace(array_keys($replacements), array_values($replacements), (string) $string);

  $string = trim($string, $separator);

  return mb_strtolower($string);
}

/**
 * Replace content in a string.
 *
 * @param string $haystack
 *   The content to search and replace in.
 * @param string $start
 *   The start of the content to replace.
 * @param string $end
 *   The end of the content to replace.
 * @param string $replacement
 *   The replacement content.
 */
function replace_content(string $haystack, string $start, string $end, string $replacement): string {
  if (!str_contains($haystack, $start)) {
    throw new \Exception('Start not found in the haystack');
  }

  if (!str_contains($haystack, $end)) {
    throw new \Exception('End not found in the haystack');
  }

  // Start should be before the end.
  if (strpos($haystack, $start) > strpos($haystack, $end)) {
    throw new \Exception('Start is after the end');
  }

  $pattern = '/' . preg_quote($start, '/') . '.*?' . preg_quote($end, '/') . '/s';
  $replacement = $start . PHP_EOL . $replacement . PHP_EOL . $end;

  return (string) preg_replace($pattern, $replacement, $haystack);
}

/**
 * Convert an array to a markdown table.
 *
 * @param array<int, string> $headers
 *   The headers for the table.
 * @param array<string, array<int, string>> $rows
 *   The rows for the table.
 *
 * @return string
 *   The markdown table.
 */
function array_to_markdown_table(array $headers, array $rows): string {
  if (empty($headers) || empty($rows)) {
    return '';
  }

  $headerRow = '| ' . implode(' | ', $headers) . ' |';
  $separatorRow = '| ' . implode(' | ', array_fill(0, count($headers), '---')) . ' |';
  $dataRows = array_map(fn(array $row): string => '| ' . implode(' | ', $row) . ' |', $rows);

  return implode("\n", array_merge([$headerRow, $separatorRow], $dataRows));
}
