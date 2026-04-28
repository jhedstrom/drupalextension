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

use Behat\Step\Given;
use Behat\Step\When;
use Behat\Step\Then;

// Execute main only when script is run directly, not when included.
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
  $base_path = is_string($options['path'] ?? NULL) ? $options['path'] : dirname(__DIR__);

  require_once $base_path . '/vendor/autoload.php';

  $context_dir = $base_path . '/src/Drupal/DrupalExtension/Context';
  $info = extract_info($context_dir, [], $base_path);

  $lenient = isset($options['warning-on-invalid']);
  $results = validate($info, $base_path);

  $tree_output = '';
  if (has_validation_errors($results)) {
    $tree_output = render_validation_tree($results);
    echo $tree_output;
    if (!$lenient) {
      exit(1);
    }
  }

  $log_dir = is_string($options['log-dir'] ?? NULL) ? $options['log-dir'] : NULL;
  if ($log_dir !== NULL) {
    write_validation_logs($tree_output, $log_dir);
  }

  $steps_markdown = PHP_EOL . render_info($info, $base_path) . PHP_EOL;
  $readme_markdown = PHP_EOL . render_info($info, $base_path, 'STEPS.md') . PHP_EOL;

  $steps_file = 'STEPS.md';
  $steps_contents = file_get_contents($base_path . DIRECTORY_SEPARATOR . $steps_file);
  if ($steps_contents === FALSE) {
    printf('Failed to read %s.' . PHP_EOL, $steps_file);
    exit(1);
  }
  $steps_replaced = replace_content($steps_contents, '# Available steps', '[//]: # (END)', $steps_markdown);

  $readme_file = 'README.md';
  $readme_contents = file_get_contents($base_path . DIRECTORY_SEPARATOR . $readme_file);
  if ($readme_contents === FALSE) {
    printf('Failed to read %s.' . PHP_EOL, $readme_file);
    exit(1);
  }
  $readme_replaced = replace_content($readme_contents, '## Available steps', '[//]: # (END)', $readme_markdown);

  if ($steps_replaced === $steps_contents && $readme_replaced === $readme_contents) {
    echo PHP_EOL . "\033[32mDocumentation is up to date. No changes were made.\033[0m" . PHP_EOL;
    exit(0);
  }

  $fail_on_change = isset($options['fail-on-change']);
  if ($fail_on_change && ($steps_replaced !== $steps_contents || $readme_replaced !== $readme_contents)) {
    echo PHP_EOL . "\033[31mDocumentation is outdated. Please regenerate documentation.\033[0m" . PHP_EOL;
    exit(1);
  }
  file_put_contents($base_path . DIRECTORY_SEPARATOR . $steps_file, $steps_replaced);
  file_put_contents($base_path . DIRECTORY_SEPARATOR . $readme_file, $readme_replaced);
  echo 'Documentation updated.' . PHP_EOL;
}

// @codeCoverageIgnoreEnd

/**
 * Parse info from the Context classes in a directory.
 *
 * @param string $context_dir
 *   The directory containing Context classes.
 * @param array<int, string> $exclude
 *   Array of class names to exclude.
 * @param string $base_path
 *   Base path for the repository.
 *
 * @return array<string, array<string, mixed>>
 *   Array of info keyed by class name. Each entry has 'name', 'methods'
 *   (array of method info with 'name', 'steps', 'description', 'example'
 *   keys), and class-level 'description' / 'description_full' keys.
 *
 * @throws \ReflectionException
 */
function extract_info(string $context_dir, array $exclude = [], string $base_path = '', string $namespace = 'Drupal\\DrupalExtension\\Context'): array {
  if (empty($base_path)) {
    // @codeCoverageIgnore
    $base_path = dirname(__DIR__);
  }

  $info = [];

  if (!is_dir($context_dir)) {
    throw new \Exception(sprintf('Context directory %s does not exist', $context_dir));
  }

  // Collect all PHP files in the context directory.
  $files = scandir($context_dir) ?: [];
  $class_files = [];
  foreach ($files as $file) {
    if (is_file($context_dir . DIRECTORY_SEPARATOR . $file) && str_ends_with($file, '.php')) {
      $class_files[] = basename($file, '.php');
    }
  }
  sort($class_files);

  foreach ($class_files as $class_name) {
    if (in_array($class_name, $exclude, TRUE)) {
      continue;
    }

    $fqcn = $namespace . '\\' . $class_name;

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

    $class_info = [
      'name' => $class_name,
      'name_contextual' => $class_name,
      'context' => $class_name,
      'methods' => [],
    ];
    $class_info += parse_class_comment($class_name, (string) $reflection->getDocComment());

    // Get all public methods declared in this class (not inherited).
    $methods = $reflection->getMethods(ReflectionMethod::IS_PUBLIC);
    foreach ($methods as $method) {
      // Only include methods declared in this class.
      if ($method->getDeclaringClass()->getName() !== $fqcn) {
        continue;
      }

      $attribute_steps = extract_step_attributes($method);
      $parsed_comment = parse_method_comment((string) $method->getDocComment(), !empty($attribute_steps));

      if (!empty($attribute_steps)) {
        $method_info = $parsed_comment ?? ['steps' => [], 'description' => '', 'example' => ''];
        $method_info['steps'] = $attribute_steps;
        $class_info['methods'][] = $method_info + ['name' => $method->getName()];
      }
      elseif ($parsed_comment) {
        $class_info['methods'][] = $parsed_comment + ['name' => $method->getName()];
      }
    }

    if (!empty($class_info['methods'])) {
      // Sort info by Given, When, Then.
      usort($class_info['methods'], static function (array $a, array $b): int {
          $order = ['@Given', '@When', '@Then'];

          $get_order_index = function ($step) use ($order): int {
            foreach ($order as $index => $prefix) {
              if (str_starts_with($step, $prefix)) {
                return $index;
              }
            }

              // @codeCoverageIgnoreStart
              return PHP_INT_MAX;
              // @codeCoverageIgnoreEnd
          };

          $a_step = $a['steps'][0] ?? '';
          $b_step = $b['steps'][0] ?? '';

          $a_index = $get_order_index($a_step);
          $b_index = $get_order_index($b_step);

          return $a_index <=> $b_index;
      });
    }

    // Only include classes that have step definitions.
    if (!empty($class_info['methods'])) {
      $info[$class_name] = $class_info;
    }
  }

  return $info;
}

/**
 * Parse class comment.
 *
 * @param string $class_name
 *   The class name.
 * @param string $comment
 *   The comment.
 *
 * @return array<string, string>
 *   Array of 'description' and 'description_full' keys.
 */
function parse_class_comment(string $class_name, string $comment): array {
  if (empty($comment)) {
    throw new \Exception(sprintf('Class comment for %s is empty', $class_name));
  }

  $comment = preg_replace('#^/\*\*|^\s*\*\/$#m', '', $comment);
  $lines = explode(PHP_EOL, (string) $comment);
  // Remove docblock asterisk and up to one space, but keep indentation.
  $lines = array_map(static fn(string $l): string => preg_replace('/^\s*\* ?/', '', $l), $lines);

  // Remove first and last empty lines.
  if (count($lines) > 1 && empty($lines[0])) {
    array_shift($lines);
  }
  if (count($lines) > 1 && empty($lines[count($lines) - 1])) {
    array_pop($lines);
  }

  // Trim lines, but preserve indentation within @code blocks.
  $in_code_block = FALSE;
  $lines = array_map(static function (string $l) use (&$in_code_block): string {
    if (str_starts_with(trim($l), '@code')) {
        $in_code_block = TRUE;
        return trim($l);
    }
    if (str_starts_with(trim($l), '@endcode')) {
        $in_code_block = FALSE;
        return trim($l);
    }
    if ($in_code_block) {
        // Preserve indentation within code blocks.
        return rtrim($l);
    }
      return trim($l);
  }, $lines);

  // @codeCoverageIgnoreStart
  if (empty($lines)) {
    throw new \Exception(sprintf('Class comment for %s is empty', $class_name));
  }
  // @codeCoverageIgnoreEnd
  $description = $lines[0];
  if (empty($description)) {
    throw new \Exception(sprintf('Class comment for %s is empty', $class_name));
  }

  if (str_starts_with($description, 'Class ')) {
    throw new \Exception(sprintf('Class comment should have a descriptive content for %s', $class_name));
  }

  $full_description = implode(PHP_EOL, $lines);

  if (substr_count($full_description, '`') % 2 !== 0) {
    throw new \Exception(sprintf('Class inline code block is not closed for %s', $class_name));
  }

  return [
    'description' => $description,
    'description_full' => $full_description,
  ];
}

/**
 * Parse comment.
 *
 * @param string $comment
 *   The comment.
 * @param bool $allow_empty_steps
 *   If TRUE, return the parsed data even when no step annotations are found.
 *   Used when step definitions come from PHP attributes instead of docblocks.
 *
 * @return array<string, array<int, string>|string>|null
 *   Array of 'steps', 'description', and 'example' keys or NULL if steps were
 *   not found in the comment and $allow_empty_steps is FALSE.
 */
function parse_method_comment(string $comment, bool $allow_empty_steps = FALSE): ?array {
  if (empty($comment)) {
    return NULL;
  }

  $return = [
    'steps' => [],
    'description' => '',
    'example' => '',
  ];

  $lines = explode(PHP_EOL, $comment);

  $example_start = FALSE;
  foreach ($lines as $line) {
    $line = str_replace('/*', '', $line);
    $line = str_replace('/**', '', $line);
    $line = str_replace('*/', '', $line);
    $line = preg_replace('/^\s*\*/', '', $line);
    $line = rtrim((string) $line, " \t\n\r\0\x0B");
    // All docblock lines start with a space.
    $line = substr($line, 1);

    if (str_starts_with($line, '@code')) {
      $example_start = TRUE;
    }
    elseif (str_starts_with($line, '@endcode')) {
      $example_start = FALSE;
    }
    elseif (str_starts_with($line, '@Given') || str_starts_with($line, '@When') || str_starts_with($line, '@Then')) {
      $line = trim($line, " \t\n\r\0\x0B");
      $return['steps'][] = $line;
    }
    else {
      if (!$example_start && empty($line)) {
        continue;
      }

      if ($example_start) {
        $line = rtrim($line, "\t\n\r\0\x0B");
        $return['example'] .= $line . PHP_EOL;
      }

      if (empty($return['description'])) {
        $line = trim($line);
        $return['description'] .= $line . ' ';
      }
    }
  }

  if ($example_start) {
    throw new \Exception('Example not closed');
  }

  if (!empty($return['steps'])) {
    // Sort the steps by Given, When, Then.
    $sorted = [];
    foreach (['@Given', '@When', '@Then'] as $step) {
      foreach ($return['steps'] as $step_item) {
        if (str_starts_with($step_item, $step)) {
          $sorted[] = $step_item;
        }
      }
    }
    $return['steps'] = $sorted;

    $return['description'] = trim($return['description']);

    if (!empty($return['example'])) {
      // Remove indentation from the example, using the first line as a
      // reference.
      $lines = explode(PHP_EOL, $return['example']);
      $first_line = '';
      foreach ($lines as $l) {
        if ($l !== '') {
          $first_line = $l;
          break;
        }
      }
      $indentation = strspn($first_line, ' ');
      foreach ($lines as $key => $line) {
        $line = rtrim($line);
        if (strlen($line) > $indentation) {
          $lines[$key] = substr($line, $indentation);
        }
      }
      $return['example'] = implode(PHP_EOL, $lines);
    }
  }

  if (empty($return['steps']) && !$allow_empty_steps) {
    return NULL;
  }

  return $return;
}

/**
 * Extract step definitions from PHP 8 attributes on a method.
 *
 * @param \ReflectionMethod $method
 *   The method to inspect.
 *
 * @return array<int, string>
 *   Array of step strings in '@Given ...' format, or empty array if no
 *   step attributes found.
 */
function extract_step_attributes(\ReflectionMethod $method): array {
  $step_map = [
    Given::class => '@Given',
    When::class => '@When',
    Then::class => '@Then',
  ];

  $steps = [];

  foreach ($method->getAttributes() as $attribute) {
    $name = $attribute->getName();
    if (isset($step_map[$name])) {
      $args = $attribute->getArguments();
      $pattern = $args[0] ?? $args['pattern'] ?? '';
      $steps[] = $step_map[$name] . ' ' . $pattern;
    }
  }

  return $steps;
}

/**
 * Convert info to content.
 *
 * @param array<string, array<string, mixed>> $info
 *   Array of info items with 'name', 'from', and 'to' keys.
 * @param string $base_path
 *   Base path for the repository.
 * @param string|null $path_for_links
 *   Path prefix for links in the index (e.g. 'STEPS.md').
 *
 * @return string
 *   Markdown table.
 */
function render_info(array $info, string $base_path = '', ?string $path_for_links = NULL): string {
  if (empty($base_path)) {
    // @codeCoverageIgnore
    $base_path = dirname(__DIR__);
  }

  $content_output = '';
  $index_rows = [];

  foreach ($info as $class => $class_info) {
    // Find the source file.
    $src_file = find_source_file($class, $base_path);
    if (!$src_file) {
      throw new \Exception(sprintf('Source file for %s does not exist', $class));
    }

    // Find the example feature file.
    $example_name = camel_to_snake(str_replace('Context', '', $class));
    $example_file = sprintf('tests/behat/features/%s.feature', $example_name);
    $example_file_path = $base_path . DIRECTORY_SEPARATOR . $example_file;

    $example_link = '';
    if (file_exists($example_file_path)) {
      $example_link = sprintf(', [Example](%s)', $example_file);
    }

    $content_output .= sprintf('## %s', (string) $class_info['name_contextual']) . PHP_EOL . PHP_EOL;
    $content_output .= sprintf('[Source](%s)%s', $src_file, $example_link) . PHP_EOL . PHP_EOL;

    // Add description as markdown-safe accommodating for lists.
    $description_full = '';
    $lines = explode(PHP_EOL, (string) $class_info['description_full']);
    $was_list = FALSE;
    $in_code_block = FALSE;
    $code_block = '';
    foreach ($lines as $line) {
      $trimmed_line = trim($line);

      // Handle @code tag - start collecting code block.
      if (str_starts_with($trimmed_line, '@code')) {
        $in_code_block = TRUE;
        $code_block = '';
        continue;
      }

      // Handle @endcode tag - wrap collected code in markdown code block.
      if (str_starts_with($trimmed_line, '@endcode')) {
        $in_code_block = FALSE;
        $description_full .= '```' . PHP_EOL;
        $description_full .= rtrim($code_block) . PHP_EOL;
        $description_full .= '```' . PHP_EOL;
        $code_block = '';
        continue;
      }

      // If inside code block, collect lines without processing.
      if ($in_code_block) {
        $code_block .= $line . PHP_EOL;
        continue;
      }

      $is_list = str_starts_with($trimmed_line, '-');

      if (!$is_list) {
        if (empty($line) && !$was_list) {
          $description_full .= $line . '<br/><br/>' . PHP_EOL;
        }
        else {
          $description_full .= $line . PHP_EOL;
        }
        $was_list = FALSE;
      }
      else {
        if (str_ends_with($description_full, '<br/><br/>' . PHP_EOL)) {
          $description_full = rtrim($description_full, '<br/><br/>' . PHP_EOL) . PHP_EOL;
        }

        $description_full .= $line . PHP_EOL;
        $was_list = TRUE;
      }
    }

    $description_full = (string) preg_replace('/^/m', '>  ', $description_full);
    $content_output .= $description_full . PHP_EOL . PHP_EOL;

    // Add to index.
    $index_rows_path = '#' . preg_replace('/[^A-Za-z0-9_\-]/', '', strtolower((string) $class_info['name_contextual']));
    if ($path_for_links) {
      $index_rows_path = $path_for_links . $index_rows_path;
    }
    $index_rows[] = [
      sprintf('[%s](%s)', (string) $class_info['name_contextual'], (string) $index_rows_path),
      (string) $class_info['description'],
    ];

    if (!is_array($class_info['methods'])) {
      continue;
    }

    foreach ($class_info['methods'] as $method) {
      $method['steps'] = is_array($method['steps']) ? $method['steps'] : [$method['steps']];
      $method['description'] = is_string($method['description']) ? $method['description'] : '';
      $method['example'] = is_string($method['example']) ? $method['example'] : '';

      $method['steps'] = array_reduce($method['steps'], fn(string $carry, string $item): string => $carry . sprintf("%s\n", $item), '');
      $method['steps'] = rtrim($method['steps'], "\n");

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

      $content_output .= strtr(
            $template,
            [
              '[description]' => $method['description'],
              '[step]' => $method['steps'],
              '[example]' => $method['example'],
            ]
        );

      $content_output .= PHP_EOL;
    }
  }

  $index_output = '';
  if (!empty($index_rows)) {
    $index_output .= array_to_markdown_table(['Class', 'Description'], $index_rows) . PHP_EOL . PHP_EOL;
  }

  $output = '';
  $output .= $index_output . PHP_EOL;

  // Render content if this is not a path for links.
  if (!$path_for_links) {
    $output .= '---' . PHP_EOL . PHP_EOL;
    $output .= $content_output . PHP_EOL;
  }

  return $output;
}

/**
 * Find the source file for a class relative to the base path.
 *
 * @param string $class_name
 *   The class name.
 * @param string $base_path
 *   Base path for the repository.
 *
 * @return string|null
 *   The relative path to the source file, or NULL if not found.
 */
function find_source_file(string $class_name, string $base_path): ?string {
  $src_file = sprintf('src/Drupal/DrupalExtension/Context/%s.php', $class_name);
  $src_file_path = $base_path . DIRECTORY_SEPARATOR . $src_file;

  if (file_exists($src_file_path)) {
    return $src_file;
  }

  // Fallback: try root src directory (for tests).
  $src_file = sprintf('src/%s.php', $class_name);
  $src_file_path = $base_path . DIRECTORY_SEPARATOR . $src_file;

  if (file_exists($src_file_path)) {
    return $src_file;
  }

  return NULL;
}

/**
 * Validate the info.
 *
 * @param array<string,array<string, array<int, array<string, array<int,string>|string>>|string>> $info
 *   Array of info items with 'name', 'from', and 'to' keys.
 * @param string $base_path
 *   Base path for the repository.
 *
 * @return array<string, array{file: array{pass: bool, path: string}, methods: array<string, array<string, array{pass: bool, messages: array<int, string>}>>}>
 *   Structured validation results per class and method.
 */
function validate(array $info, string $base_path = ''): array {
  if (empty($base_path)) {
    // @codeCoverageIgnore
    $base_path = dirname(__DIR__);
  }

  $results = [];

  foreach ($info as $class_info) {
    $class_name = is_string($class_info['name']) ? $class_info['name'] : '';

    // Check example file.
    $example_name = camel_to_snake(str_replace('Context', '', $class_name));
    $example_file = sprintf('tests/behat/features/%s.feature', $example_name);
    $example_file_path = $base_path . DIRECTORY_SEPARATOR . $example_file;

    $class_result = [
      'file' => [
        'pass' => file_exists($example_file_path),
        'path' => $example_file,
      ],
      'methods' => [],
    ];

    if (!is_array($class_info['methods'])) {
      $results[$class_name] = $class_result;
      continue;
    }

    foreach ($class_info['methods'] as $method) {
      $method['steps'] = is_array($method['steps']) ? $method['steps'] : [$method['steps']];
      $method['name'] = is_string($method['name']) ? $method['name'] : '';
      $method['description'] = is_string($method['description']) ? $method['description'] : '';
      $method['example'] = is_string($method['example']) ? $method['example'] : '';

      $step = (string) $method['steps'][0];

      // Step wording check.
      $step_wording = ['pass' => TRUE, 'messages' => []];
      if (str_starts_with($step, '@Given') && str_ends_with($step, ':') && !str_contains($step, 'following')) {
        $step_wording['pass'] = FALSE;
        $step_wording['messages'][] = 'Missing "following" in the step';
      }
      if (str_starts_with($step, '@When') && !str_contains($step, 'I ')) {
        $step_wording['pass'] = FALSE;
        $step_wording['messages'][] = 'Missing "I " in the step';
      }
      if (str_starts_with($step, '@Then')) {
        if (!str_contains($step, ' should ')) {
          $step_wording['pass'] = FALSE;
          $step_wording['messages'][] = 'Missing "should" in the step';
        }
        if (!(str_contains($step, ' the ') || str_contains($step, ' a ') || str_contains($step, ' no '))) {
          $step_wording['pass'] = FALSE;
          $step_wording['messages'][] = 'Missing "the", "a" or "no" in the step';
        }
      }

      // Method naming check.
      $method_naming = ['pass' => TRUE, 'messages' => []];
      if (str_starts_with($step, '@Then')) {
        if (!str_contains((string) $method['name'], 'Assert')) {
          $method_naming['pass'] = FALSE;
          $method_naming['messages'][] = 'Missing "Assert" in the method name';
        }
        if (str_contains((string) $method['name'], 'Should')) {
          $method_naming['pass'] = FALSE;
          $method_naming['messages'][] = 'Contains "Should" in the method name';
        }
      }

      // Single step check.
      $single_step = ['pass' => TRUE, 'messages' => []];
      if (count($method['steps']) > 1) {
        $single_step['pass'] = FALSE;
        $single_step['messages'][] = 'Multiple steps found';
      }

      // Has example check.
      $has_example = ['pass' => TRUE, 'messages' => []];
      if (empty($method['example'])) {
        $has_example['pass'] = FALSE;
        $has_example['messages'][] = 'Missing example';
      }

      // Unnecessary regex check.
      $regex_convertible = ['pass' => TRUE, 'messages' => []];
      $suggested = regex_to_turnip($step);
      if ($suggested !== NULL) {
        $regex_convertible['pass'] = FALSE;
        $regex_convertible['messages'][] = $step;
        $regex_convertible['messages'][] = $suggested;
      }

      $class_result['methods'][$method['name']] = [
        'step_wording' => $step_wording,
        'method_naming' => $method_naming,
        'single_step' => $single_step,
        'has_example' => $has_example,
        'regex_convertible' => $regex_convertible,
      ];
    }

    $results[$class_name] = $class_result;
  }

  return $results;
}

/**
 * Check if validation results contain any errors.
 *
 * @param array<string, array{file: array{pass: bool, path: string}, methods: array<string, array<string, array{pass: bool, messages: array<int, string>}>>}> $results
 *   Structured validation results from validate().
 *
 * @return bool
 *   TRUE if there are validation errors.
 */
function has_validation_errors(array $results): bool {
  foreach ($results as $class_result) {
    foreach ($class_result['methods'] as $method_checks) {
      foreach ($method_checks as $check) {
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
 * @param array<string, array{file: array{pass: bool, path: string}, methods: array<string, array<string, array{pass: bool, messages: array<int, string>}>>}> $results
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
  $total_classes = count($results);
  $total_methods = 0;
  $total_violations = 0;
  $counts = [
    'step_wording' => 0,
    'method_naming' => 0,
    'single_step' => 0,
    'has_example' => 0,
    'regex_convertible' => 0,
    'file' => 0,
  ];
  foreach ($results as $class_result) {
    if (!$class_result['file']['pass']) {
      $counts['file']++;
      $total_violations++;
    }
    foreach ($class_result['methods'] as $method_checks) {
      $total_methods++;
      foreach (array_keys($symbols) as $key) {
        if (!$method_checks[$key]['pass']) {
          $counts[$key]++;
          $total_violations++;
        }
      }
    }
  }

  $output = '';
  $output .= $yellow . 'Validation warnings:' . $reset . PHP_EOL . PHP_EOL;

  $class_names = array_keys($results);

  foreach ($class_names as $class_name) {
    $class_result = $results[$class_name];
    $output .= $bold . $class_name . $reset . PHP_EOL;

    $method_names = array_keys($class_result['methods']);
    $total_children = 1 + count($method_names);
    $child_index = 0;

    // File check.
    $child_index++;
    $is_last = ($child_index === $total_children);
    $branch = $is_last ? '└── ' : '├── ';

    $file_cont = $is_last ? '    ' : '│   ';
    if ($class_result['file']['pass']) {
      $output .= '  ' . $branch . $green . '■' . $reset . ' Example file present' . PHP_EOL;
    }
    else {
      $output .= '  ' . $branch . $yellow . '□' . $reset . ' Example file absent' . PHP_EOL;
      $output .= '  ' . $file_cont . '  ' . $dim . $class_result['file']['path'] . $reset . PHP_EOL;
    }

    // Methods.
    foreach ($method_names as $method_name) {
      $child_index++;
      $is_last_method = ($child_index === $total_children);
      $method_branch = $is_last_method ? '└── ' : '├── ';
      $method_cont = $is_last_method ? '    ' : '│   ';

      $output .= '  ' . $method_branch . $method_name . PHP_EOL;

      $checks = $class_result['methods'][$method_name];
      $check_keys = array_keys($symbols);
      $total_checks = count($check_keys);

      foreach ($check_keys as $ki => $check_key) {
        $check = $checks[$check_key];
        $sym = $symbols[$check_key];
        $is_last_check = ($ki === $total_checks - 1);
        $check_branch = $is_last_check ? '└── ' : '├── ';
        $check_cont = $is_last_check ? '      ' : '│     ';

        if ($check['pass']) {
          $output .= '  ' . $method_cont . $check_branch . $green . $sym['pass'] . $reset . ' ' . $sym['label'] . PHP_EOL;
        }
        else {
          $output .= '  ' . $method_cont . $check_branch . $yellow . $sym['warn'] . $reset . ' ' . $sym['label'] . PHP_EOL;
          foreach ($check['messages'] as $message) {
            $output .= '  ' . $method_cont . $check_cont . $dim . $message . $reset . PHP_EOL;
          }
        }
      }
    }

    $output .= PHP_EOL;
  }

  // Summary.
  $output .= $yellow . 'Summary:' . $reset . PHP_EOL;
  $output .= '  Scanned ' . $total_classes . ' classes, ' . $total_methods . ' steps.' . PHP_EOL . PHP_EOL;

  $summary_lines = [
    [
      'Step wording', $symbols['step_wording'],
      $total_methods - $counts['step_wording'], $counts['step_wording'], $total_methods,
    ],
    [
      'Method naming', $symbols['method_naming'],
      $total_methods - $counts['method_naming'], $counts['method_naming'], $total_methods,
    ],
    [
      'Single step', $symbols['single_step'],
      $total_methods - $counts['single_step'], $counts['single_step'], $total_methods,
    ],
    [
      'Example', $symbols['has_example'],
      $total_methods - $counts['has_example'], $counts['has_example'], $total_methods,
    ],
    [
      'Regex usage', $symbols['regex_convertible'],
      $total_methods - $counts['regex_convertible'], $counts['regex_convertible'], $total_methods,
    ],
    [
      'Example file', ['pass' => '■', 'warn' => '□'],
      $total_classes - $counts['file'], $counts['file'], $total_classes,
    ],
  ];

  foreach ($summary_lines as [$label, $sym, $pass_count, $fail_count, $total]) {
    $line_color = $fail_count > 0 ? $yellow : $green;
    $check = $fail_count === 0 ? '✓' : '⚠';
    $pass_str = str_pad((string) $pass_count, 3, ' ', STR_PAD_LEFT);
    $fail_str = str_pad((string) $fail_count, 3, ' ', STR_PAD_LEFT);
    $output .= $line_color . '  ' . sprintf('%-15s', $label) . $sym['pass'] . $pass_str . '  ' . $sym['warn'] . $fail_str . '  ' . $check . $reset . PHP_EOL;
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
 * @param string $tree_output
 *   The rendered validation tree (with ANSI codes).
 * @param string $log_dir
 *   The directory to write the log files to.
 */
function write_validation_logs(string $tree_output, string $log_dir): void {
  if (!is_dir($log_dir)) {
    mkdir($log_dir, 0777, TRUE);
  }

  // Strip ANSI escape codes.
  $plain = (string) preg_replace('/\033\[[0-9;]*m/', '', $tree_output);

  if (empty(trim($plain))) {
    file_put_contents($log_dir . '/validation-summary.txt', 'No validation warnings.' . PHP_EOL);
    file_put_contents($log_dir . '/validation-details.txt', '');
    return;
  }

  // Split at "Summary:" line.
  $parts = explode('Summary:', $plain, 2);
  $details = trim($parts[0]);
  $summary = isset($parts[1]) ? 'Summary:' . $parts[1] : $details;

  file_put_contents($log_dir . '/validation-summary.txt', $summary);
  file_put_contents($log_dir . '/validation-details.txt', $details . PHP_EOL);
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
  $arg_count = 0;
  $converted = preg_replace_callback('#\(([^)]*)\)#', function (array $m) use (&$arg_count): string {
      $inner = $m[1];
      // Simple quoted string capture: [^"]*  or [^']*.
    if ($inner === '[^"]*' || $inner === "[^']*") {
        $arg_count++;
        return ':arg' . $arg_count;
    }
      // Simple unquoted capture: .*  or .+.
    if ($inner === '.*' || $inner === '.+') {
        $arg_count++;
        return ':arg' . $arg_count;
    }
      // Numeric capture: \d+ or [0-9]+.
    if ($inner === '\d+' || $inner === '[0-9]+') {
        $arg_count++;
        return ':arg' . $arg_count;
    }
      // Word capture: \w+.
    if ($inner === '\w+') {
        $arg_count++;
        return ':arg' . $arg_count;
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
  $literal_check = preg_replace('#\\\\[/"\'.]#', '', $converted);
  // If there are remaining backslash sequences or regex metacharacters, bail.
  if ($literal_check !== NULL && preg_match('#[\\\\.*+?\[\]{}|^$]#', $literal_check)) {
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
    $lower_case_char = mb_strtolower($char);
    if ($lower_case_char !== $char && $key !== 0) {
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
 * @param array<string|int, array<int|string, string>> $rows
 *   The rows for the table.
 *
 * @return string
 *   The markdown table.
 */
function array_to_markdown_table(array $headers, array $rows): string {
  if (empty($headers) || empty($rows)) {
    return '';
  }

  $header_row = '| ' . implode(' | ', $headers) . ' |';
  $separator_row = '| ' . implode(' | ', array_fill(0, count($headers), '---')) . ' |';
  $data_rows = array_map(fn(array $row): string => '| ' . implode(' | ', $row) . ' |', $rows);

  return implode("\n", array_merge([$header_row, $separator_row], $data_rows));
}
