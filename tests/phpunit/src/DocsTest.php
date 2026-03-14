<?php

declare(strict_types=1);

namespace Drupal\DrupalExtension\Tests;

use PHPUnit\Framework\Attributes\CoversFunction;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Tests for docs generation.
 *
 * phpcs:disable Drupal.Commenting.FunctionComment
 */
#[CoversFunction('parse_method_comment')]
#[CoversFunction('camel_to_snake')]
#[CoversFunction('array_to_markdown_table')]
#[CoversFunction('render_info')]
#[CoversFunction('validate')]
#[CoversFunction('has_validation_errors')]
#[CoversFunction('render_validation_tree')]
#[CoversFunction('replace_content')]
#[CoversFunction('extract_info')]
#[CoversFunction('parse_class_comment')]
#[CoversFunction('find_source_file')]
#[CoversFunction('regex_to_turnip')]
#[CoversFunction('write_validation_logs')]
class DocsTest extends TestCase
{

    /**
     * Temporary directory for tests.
     */
    protected static string $tmp = '';

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        parent::setUp();

        require_once __DIR__ . '/../../../scripts/docs.php';

        // Pre-load all fixture context classes.
        $fixturesDir = __DIR__ . '/../fixtures/docs';
        $fixtureFiles = glob($fixturesDir . '/*.php');
        if ($fixtureFiles !== false) {
            foreach ($fixtureFiles as $fixtureFile) {
                require_once $fixtureFile;
            }
        }

        // Create a unique temporary directory for each test.
        static::$tmp = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'docs_test_' . uniqid();
        mkdir(static::$tmp, 0777, true);
    }

    /**
     * {@inheritdoc}
     */
    protected function tearDown(): void
    {
        parent::tearDown();

        // Clean up temporary directory.
        if (static::$tmp && is_dir(static::$tmp)) {
            $this->removeDirectory(static::$tmp);
        }
    }

    /**
     * Recursively remove a directory.
     */
    private function removeDirectory(string $dir): void
    {
        $items = scandir($dir) ?: [];
        foreach ($items as $item) {
            if ($item === '.') {
                continue;
            }
            if ($item === '..') {
                continue;
            }
            $path = $dir . DIRECTORY_SEPARATOR . $item;
            if (is_dir($path)) {
                $this->removeDirectory($path);
            } else {
                unlink($path);
            }
        }
        rmdir($dir);
    }

    #[DataProvider('dataProviderParseMethodComment')]
    public function testParseMethodComment(string $comment, ?array $expected, ?string $exception = null): void
    {
        if ($exception) {
            $this->expectException(\Exception::class);
            $this->expectExceptionMessage($exception);
        }

        $actual = parse_method_comment($comment);

        $this->assertEquals($expected, $actual);
    }

    public static function dataProviderParseMethodComment(): \Iterator
    {
        yield 'empty' => [
            '',
            null,
        ];
        yield 'no steps' => [
            <<<'EOD'
/**
 * This is a description.
 *
 * @param string $test
 */
EOD,
            null,
        ];
        yield 'with steps' => [
            <<<'EOD'
/**
 * This is a description.
 *
 * @Given I am on the homepage
 */
EOD,
            [
                'steps' => ['@Given I am on the homepage'],
                'description' => 'This is a description.',
                'example' => '',
            ],
        ];
        yield 'multiple steps' => [
            <<<'EOD'
/**
 * This is a description.
 *
 * @Given I am on the homepage
 * @When I click on the button
 * @Then I should see the text
 */
EOD,
            [
                'steps' => ['@Given I am on the homepage', '@When I click on the button', '@Then I should see the text'],
                'description' => 'This is a description.',
                'example' => '',
            ],
        ];
        yield 'with example' => [
            <<<'EOD'
/**
 * This is a description.
 *
 * @Given I am on the homepage
 *
 * @code
 * Given I am on the homepage
 * @endcode
 */
EOD,
            [
                'steps' => ['@Given I am on the homepage'],
                'description' => 'This is a description.',
                'example' => "Given I am on the homepage\n",
            ],
        ];
        yield 'with indented example' => [
            <<<'EOD'
/**
 * This is a description.
 *
 * @Given I am on the homepage
 *
 * @code
 *   Given I am on the homepage
 *   When I click "Submit"
 * @endcode
 */
EOD,
            [
                'steps' => ['@Given I am on the homepage'],
                'description' => 'This is a description.',
                'example' => "Given I am on the homepage\nWhen I click \"Submit\"\n",
            ],
        ];
        yield 'multiline description' => [
            <<<'EOD'
/**
 * This is a description
 * that spans multiple lines.
 *
 * @Given I am on the homepage
 */
EOD,
            [
                'steps' => ['@Given I am on the homepage'],
                'description' => 'This is a description',
                'example' => '',
            ],
        ];
        yield 'steps out of order' => [
            <<<'EOD'
/**
 * This is a description.
 *
 * @When I click on the button
 * @Given I am on the homepage
 * @Then I should see the text
 */
EOD,
            [
                'steps' => ['@Given I am on the homepage', '@When I click on the button', '@Then I should see the text'],
                'description' => 'This is a description.',
                'example' => '',
            ],
        ];
        yield 'complex example with empty lines' => [
            <<<'EOD'
/**
 * This is a description.
 *
 * @Given I am on the homepage
 *
 * @code
 *   Given I am on the homepage
 *
 *   When I click "Submit"
 *   Then I should see "Success"
 * @endcode
 */
EOD,
            [
                'steps' => ['@Given I am on the homepage'],
                'description' => 'This is a description.',
                'example' => "Given I am on the homepage\n\nWhen I click \"Submit\"\nThen I should see \"Success\"\n",
            ],
        ];
        yield 'comment with comment markers' => [
            <<<'EOD'
/**
 * This is a description.
 * /* nested comment start
 * */ nested comment end
 * @Given I am on the homepage
 */
EOD,
            [
                'steps' => ['@Given I am on the homepage'],
                'description' => 'This is a description.',
                'example' => '',
            ],
        ];
        yield 'unclosed example error' => [
            <<<'EOD'
/**
 * This is a description.
 *
 * @Given I am on the homepage
 *
 * @code
 * Example without closing tag
 */
EOD,
            null,
            'Example not closed',
        ];
        yield 'example without steps' => [
            <<<'EOD'
/**
 * This is a description.
 *
 * @code
 * Example code
 * @endcode
 */
EOD,
            null,
        ];
        yield 'trim description' => [
            <<<'EOD'
/**
 * This is a description with trailing space.
 *
 * @Given I am on the homepage
 */
EOD,
            [
                'steps' => ['@Given I am on the homepage'],
                'description' => 'This is a description with trailing space.',
                'example' => '',
            ],
        ];
    }

    #[DataProvider('dataProviderCamelToSnake')]
    public function testCamelToSnake(string $input, string $expected, string $separator = '_'): void
    {
        $actual = camel_to_snake($input, $separator);
        $this->assertEquals($expected, $actual);
    }

    public static function dataProviderCamelToSnake(): \Iterator
    {
        yield 'simple camelCase' => [
            'camelCase',
            'camel_case',
        ];
        yield 'PascalCase' => [
            'PascalCase',
            'pascal_case',
        ];
        yield 'already_snake_case' => [
            'already_snake_case',
            'already_snake_case',
        ];
        yield 'numbers in camelCase' => [
            'user123Name',
            'user_123_name',
        ];
        yield 'multiple uppercase in a row' => [
            'HTTPRequest',
            'h_t_t_p_request',
        ];
        yield 'custom separator' => [
            'camelCase',
            'camel-case',
            '-',
        ];
        yield 'mixed case with numbers' => [
            'getAPI2Config',
            'get_a_p_i_2_config',
        ];
        yield 'single character uppercase' => [
            'aB',
            'a_b',
        ];
        yield 'single letter' => [
            'A',
            'a',
        ];
        yield 'starts with uppercase' => [
            'FileContext',
            'file_context',
        ];
        yield 'acronym at end' => [
            'userAPI',
            'user_a_p_i',
        ];
        yield 'empty string' => [
            '',
            '',
        ];
        yield 'special characters preserved' => [
            'special$Case',
            'special$_case',
        ];
        yield 'numbers only' => [
            '123',
            '123',
        ];
        yield 'snake case with custom separator' => [
            'snake_case_example',
            'snake_case_example',
            '-',
        ];
    }

    #[DataProvider('dataProviderArrayToMarkdownTable')]
    public function testArrayToMarkdownTable(array $headers, array $rows, string $expected): void
    {
        $actual = array_to_markdown_table($headers, $rows);
        $this->assertEquals($expected, $actual);
    }

    public static function dataProviderArrayToMarkdownTable(): \Iterator
    {
        yield 'basic table' => [
            ['Header 1', 'Header 2'],
            [
                'row1' => ['Cell 1', 'Cell 2'],
                'row2' => ['Cell 3', 'Cell 4'],
            ],
            "| Header 1 | Header 2 |\n| --- | --- |\n| Cell 1 | Cell 2 |\n| Cell 3 | Cell 4 |",
        ];
        yield 'single column table' => [
            ['Header'],
            [
                'row1' => ['Cell 1'],
                'row2' => ['Cell 2'],
            ],
            "| Header |\n| --- |\n| Cell 1 |\n| Cell 2 |",
        ];
        yield 'single row table' => [
            ['Header 1', 'Header 2'],
            [
                'row1' => ['Cell 1', 'Cell 2'],
            ],
            "| Header 1 | Header 2 |\n| --- | --- |\n| Cell 1 | Cell 2 |",
        ];
        yield 'empty headers' => [
            [],
            [
                'row1' => ['Cell 1', 'Cell 2'],
            ],
            '',
        ];
        yield 'empty rows' => [
            ['Header 1', 'Header 2'],
            [],
            '',
        ];
        yield 'empty headers and rows' => [
            [],
            [],
            '',
        ];
        yield 'with empty cells' => [
            ['Header 1', 'Header 2', 'Header 3'],
            [
                'row1' => ['Cell 1', '', 'Cell 3'],
                'row2' => ['', 'Cell 5', ''],
            ],
            "| Header 1 | Header 2 | Header 3 |\n| --- | --- | --- |\n| Cell 1 |  | Cell 3 |\n|  | Cell 5 |  |",
        ];
    }

    #[DataProvider('dataProviderRenderInfo')]
    public function testRenderInfo(array $info, string $expected, ?string $exception = null): void
    {
        if ($exception) {
            $this->expectException(\Exception::class);
            $exception = str_replace('@tmp', static::$tmp, $exception);
            $this->expectExceptionMessage($exception);
        }

        $basePath = static::$tmp;

        // Create temporary files for testing.
        $srcDir = $basePath . DIRECTORY_SEPARATOR . 'src';
        $featuresDir = $basePath . DIRECTORY_SEPARATOR . 'tests/behat/features';

        // Ensure directories exist.
        if (!is_dir($srcDir)) {
            mkdir($srcDir, 0777, true);
        }
        if (!is_dir($featuresDir)) {
            mkdir($featuresDir, 0777, true);
        }

        // Create sample files that the function will check for existence.
        foreach ($info as $class => $data) {
            // Update test data to include name_contextual if it doesn't exist.
            if (!isset($data['name_contextual'])) {
                $info[$class]['name_contextual'] = $class;
            }

            if ($class !== 'MissingContext') {
                // Create the source file.
                $srcFile = sprintf('src/%s.php', $class);
                $srcFilePath = $basePath . DIRECTORY_SEPARATOR . $srcFile;
                file_put_contents($srcFilePath, '<?php');
            }

            $exampleName = camel_to_snake(str_replace('Context', '', $class));
            $exampleFile = sprintf('tests/behat/features/%s.feature', $exampleName);
            $exampleFilePath = $basePath . DIRECTORY_SEPARATOR . $exampleFile;
            file_put_contents($exampleFilePath, 'Feature: Test');
        }

        // For the missing file test.
        if (isset($info['MissingContext'])) {
            $srcFilePath = $basePath . DIRECTORY_SEPARATOR . 'src/MissingContext.php';
            @unlink($srcFilePath);
        }

        $actual = render_info($info, $basePath);

        // Only test for certain elements instead of exact formatting.
        if ($exception === null && !empty($info)) {
            // Verify index table exists.
            foreach ($info as $class => $data) {
                $nameContextual = $data['name_contextual'] ?? $class;
                $linkId = strtolower(preg_replace('/[^A-Za-z0-9_\-]/', '', $nameContextual));
                $this->assertStringContainsString(sprintf("[%s](#%s)", $nameContextual, $linkId), $actual);
                $this->assertStringContainsString($data['description'], $actual);
            }

            // Verify class sections exist.
            foreach ($info as $class => $data) {
                $nameContextual = $data['name_contextual'] ?? $class;
                $this->assertStringContainsString(sprintf("## %s", $nameContextual), $actual);
                $this->assertStringContainsString("[Source](src", $actual);

                // Verify step details for each method.
                if (isset($data['methods']) && is_array($data['methods'])) {
                    foreach ($data['methods'] as $method) {
                        if (isset($method['steps']) && is_array($method['steps'])) {
                            foreach ($method['steps'] as $step) {
                                $this->assertStringContainsString($step, $actual);
                            }
                        } elseif (isset($method['steps']) && is_string($method['steps'])) {
                            $this->assertStringContainsString($method['steps'], $actual);
                        }

                        if (isset($method['example'])) {
                            $this->assertStringContainsString("```gherkin", $actual);

                            if (isset($method['example']) && $method['example'] === 123) {
                                // Skip this check.
                            } else {
                                $example = is_string($method['example']) ? $method['example'] : (string) $method['example'];
                                if (!empty($example)) {
                                    $exampleLines = explode("\n", $example);
                                    foreach ($exampleLines as $line) {
                                        if (!empty(trim($line))) {
                                                $this->assertStringContainsString($line, $actual);
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        } elseif (empty($info)) {
            $this->assertStringNotContainsString('<details>', $actual);
            $this->assertStringNotContainsString('[Source]', $actual);
        }
    }

    public static function dataProviderRenderInfo(): \Iterator
    {
        yield 'single context with single method' => [
            [
                'TestContext' => [
                    'name' => 'TestContext',
                    'context' => 'TestContext',
                    'description' => 'Test context description',
                    'description_full' => 'Test context description',
                    'methods' => [
                        [
                            'name' => 'testMethod',
                            'steps' => ['@Given I am on the homepage'],
                            'description' => 'Test method description',
                            'example' => 'Given I am on the homepage',
                        ],
                    ],
                ],
            ],
            '',
        ];
        yield 'multiple contexts with methods' => [
            [
                'FirstContext' => [
                    'name' => 'FirstContext',
                    'context' => 'FirstContext',
                    'description' => 'First context description',
                    'description_full' => 'First context description',
                    'methods' => [
                        [
                            'name' => 'firstMethod',
                            'steps' => ['@Given I am on the homepage'],
                            'description' => 'First method description',
                            'example' => 'Given I am on the homepage',
                        ],
                    ],
                ],
                'SecondContext' => [
                    'name' => 'SecondContext',
                    'context' => 'SecondContext',
                    'description' => 'Second context description',
                    'description_full' => 'Second context description',
                    'methods' => [
                        [
                            'name' => 'secondMethod',
                            'steps' => ['@When I click "Submit"'],
                            'description' => 'Second method description',
                            'example' => 'When I click "Submit"',
                        ],
                    ],
                ],
            ],
            '',
        ];
        yield 'empty info' => [
            [],
            '',
        ];
        yield 'with missing source file' => [
            [
                'MissingContext' => [
                    'name' => 'MissingContext',
                    'context' => 'MissingContext',
                    'description' => 'Missing context description',
                    'description_full' => 'Missing context description',
                    'methods' => [
                        [
                            'name' => 'testMethod',
                            'steps' => ['@Given I am on the homepage'],
                            'description' => 'Test method description',
                            'example' => 'Given I am on the homepage',
                        ],
                    ],
                ],
            ],
            '',
            'Source file',
        ];
        yield 'context with @code block in description' => [
            [
                'CodeBlockContext' => [
                    'name' => 'CodeBlockContext',
                    'context' => 'CodeBlockContext',
                    'description' => 'Code block context description',
                    'description_full' => "Code block context description\n\n@code\nGiven I am on the homepage\nWhen I click \"Submit\"\n@endcode",
                    'methods' => [
                        [
                            'name' => 'testMethod',
                            'steps' => ['@Given I am on the homepage'],
                            'description' => 'Test method description',
                            'example' => 'Given I am on the homepage',
                        ],
                    ],
                ],
            ],
            '',
        ];
    }

    #[DataProvider('dataProviderRenderInfoWithPathForLinks')]
    public function testRenderInfoWithPathForLinks(array $info, string $path_for_links): void
    {
        $basePath = static::$tmp;

        // Create temporary files for testing.
        $srcDir = $basePath . DIRECTORY_SEPARATOR . 'src';
        $featuresDir = $basePath . DIRECTORY_SEPARATOR . 'tests/behat/features';

        // Ensure directories exist.
        if (!is_dir($srcDir)) {
            mkdir($srcDir, 0777, true);
        }
        if (!is_dir($featuresDir)) {
            mkdir($featuresDir, 0777, true);
        }

        // Create sample files.
        foreach ($info as $class => $data) {
            if (!isset($data['name_contextual'])) {
                $info[$class]['name_contextual'] = $class;
            }

            $srcFile = sprintf('src/%s.php', $class);
            file_put_contents($basePath . DIRECTORY_SEPARATOR . $srcFile, '<?php');

            $exampleName = camel_to_snake(str_replace('Context', '', $class));
            $exampleFile = sprintf('tests/behat/features/%s.feature', $exampleName);
            file_put_contents($basePath . DIRECTORY_SEPARATOR . $exampleFile, 'Feature: Test');
        }

        $actual = render_info($info, $basePath, $path_for_links);

        // Verify that the path_for_links is used in the index.
        foreach ($info as $class => $data) {
            $nameContextual = $data['name_contextual'] ?? $class;
            $linkId = strtolower(preg_replace('/[^A-Za-z0-9_\-]/', '', $nameContextual));
            $expectedLink = sprintf("%s#%s", $path_for_links, $linkId);
            $this->assertStringContainsString($expectedLink, $actual);
        }

        // When path_for_links is set, the actual content should not be rendered.
        $this->assertStringNotContainsString('<details>', $actual);
        $this->assertStringNotContainsString('[Source]', $actual);
    }

    public static function dataProviderRenderInfoWithPathForLinks(): \Iterator
    {
        yield 'with STEPS.md path' => [
            [
                'TestContext' => [
                    'name' => 'TestContext',
                    'context' => 'TestContext',
                    'description' => 'Test context description',
                    'description_full' => 'Test context description',
                    'methods' => [
                        [
                            'name' => 'testMethod',
                            'steps' => ['@Given I am on the homepage'],
                            'description' => 'Test method description',
                            'example' => 'Given I am on the homepage',
                        ],
                    ],
                ],
            ],
            'STEPS.md',
        ];
        yield 'with custom path' => [
            [
                'FirstContext' => [
                    'name' => 'FirstContext',
                    'context' => 'FirstContext',
                    'description' => 'First context description',
                    'description_full' => 'First context description',
                    'methods' => [
                        [
                            'name' => 'firstMethod',
                            'steps' => ['@Given I am on the homepage'],
                            'description' => 'First method description',
                            'example' => 'Given I am on the homepage',
                        ],
                    ],
                ],
            ],
            'docs/REFERENCE.md',
        ];
    }

    #[DataProvider('dataProviderValidate')]
    public function testValidate(array $info, string $method_name, string $check_key, bool $expected_pass, array $expected_messages): void
    {
        $results = validate($info, static::$tmp);

        if (empty($info)) {
            $this->assertEmpty($results);
            return;
        }

        $className = array_key_first($info);
        $this->assertArrayHasKey($className, $results);

        if (empty($method_name)) {
            return;
        }

        $this->assertArrayHasKey($method_name, $results[$className]['methods']);
        $check = $results[$className]['methods'][$method_name][$check_key];
        $this->assertSame($expected_pass, $check['pass']);
        $this->assertSame($expected_messages, $check['messages']);
    }

    public static function dataProviderValidate(): \Iterator
    {
        yield 'empty info' => [
            [],
            '',
            '',
            true,
            [],
        ];
        yield 'valid given' => [
            [
                'TestContext' => [
                    'name' => 'TestContext',
                    'methods' => [
                        ['name' => 'testGivenMethod', 'steps' => ['@Given the following items:'], 'description' => 'Desc', 'example' => 'Given the following items:'],
                    ],
                ],
            ],
            'testGivenMethod',
            'step_wording',
            true,
            [],
        ];
        yield 'valid when' => [
            [
                'TestContext' => [
                    'name' => 'TestContext',
                    'methods' => [
                        ['name' => 'testWhenMethod', 'steps' => ['@When I click on the button'], 'description' => 'Desc', 'example' => 'Example'],
                    ],
                ],
            ],
            'testWhenMethod',
            'step_wording',
            true,
            [],
        ];
        yield 'valid then' => [
            [
                'TestContext' => [
                    'name' => 'TestContext',
                    'methods' => [
                        ['name' => 'testThenAssertMethod', 'steps' => ['@Then the page should contain "text"'], 'description' => 'Desc', 'example' => 'Example'],
                    ],
                ],
            ],
            'testThenAssertMethod',
            'step_wording',
            true,
            [],
        ];
        yield 'multiple steps' => [
            [
                'TestContext' => [
                    'name' => 'TestContext',
                    'methods' => [
                        ['name' => 'testMethod', 'steps' => ['@Given step one', '@Given step two'], 'description' => 'Desc', 'example' => 'Example'],
                    ],
                ],
            ],
            'testMethod',
            'single_step',
            false,
            ['Multiple steps found'],
        ];
        yield 'given without following' => [
            [
                'TestContext' => [
                    'name' => 'TestContext',
                    'methods' => [
                        ['name' => 'testMethod', 'steps' => ['@Given items:'], 'description' => 'Desc', 'example' => 'Example'],
                    ],
                ],
            ],
            'testMethod',
            'step_wording',
            false,
            ['Missing "following" in the step'],
        ];
        yield 'when without I' => [
            [
                'TestContext' => [
                    'name' => 'TestContext',
                    'methods' => [
                        ['name' => 'testMethod', 'steps' => ['@When click on button'], 'description' => 'Desc', 'example' => 'Example'],
                    ],
                ],
            ],
            'testMethod',
            'step_wording',
            false,
            ['Missing "I " in the step'],
        ];
        yield 'then without assert in method' => [
            [
                'TestContext' => [
                    'name' => 'TestContext',
                    'methods' => [
                        ['name' => 'testMethod', 'steps' => ['@Then the page should contain "text"'], 'description' => 'Desc', 'example' => 'Example'],
                    ],
                ],
            ],
            'testMethod',
            'method_naming',
            false,
            ['Missing "Assert" in the method name'],
        ];
        yield 'then with should in method' => [
            [
                'TestContext' => [
                    'name' => 'TestContext',
                    'methods' => [
                        ['name' => 'testAssertShouldMethod', 'steps' => ['@Then the page should contain "text"'], 'description' => 'Desc', 'example' => 'Example'],
                    ],
                ],
            ],
            'testAssertShouldMethod',
            'method_naming',
            false,
            ['Contains "Should" in the method name'],
        ];
        yield 'then without should in step' => [
            [
                'TestContext' => [
                    'name' => 'TestContext',
                    'methods' => [
                        ['name' => 'testAssertMethod', 'steps' => ['@Then the page contains "text"'], 'description' => 'Desc', 'example' => 'Example'],
                    ],
                ],
            ],
            'testAssertMethod',
            'step_wording',
            false,
            ['Missing "should" in the step'],
        ];
        yield 'then without the/a/no' => [
            [
                'TestContext' => [
                    'name' => 'TestContext',
                    'methods' => [
                        ['name' => 'testAssertMethod', 'steps' => ['@Then page should contain "text"'], 'description' => 'Desc', 'example' => 'Example'],
                    ],
                ],
            ],
            'testAssertMethod',
            'step_wording',
            false,
            ['Missing "the", "a" or "no" in the step'],
        ];
        yield 'missing example' => [
            [
                'TestContext' => [
                    'name' => 'TestContext',
                    'methods' => [
                        ['name' => 'testMethod', 'steps' => ['@Given the following items:'], 'description' => 'Desc', 'example' => ''],
                    ],
                ],
            ],
            'testMethod',
            'has_example',
            false,
            ['Missing example'],
        ];
        yield 'then multiple step wording errors' => [
            [
                'TestContext' => [
                    'name' => 'TestContext',
                    'methods' => [
                        ['name' => 'testAssertMethod', 'steps' => ['@Then page contains "text"'], 'description' => 'Desc', 'example' => 'Example'],
                    ],
                ],
            ],
            'testAssertMethod',
            'step_wording',
            false,
            ['Missing "should" in the step', 'Missing "the", "a" or "no" in the step'],
        ];
        yield 'edge case special chars' => [
            [
                'TestContext' => [
                    'name' => 'TestContext',
                    'methods' => [
                        ['name' => 'testAssertMethod', 'steps' => ['@Then the page should contain "text with special chars: @!#$%^"'], 'description' => 'Desc', 'example' => 'Example'],
                    ],
                ],
            ],
            'testAssertMethod',
            'step_wording',
            true,
            [],
        ];
        yield 'edge case with a' => [
            [
                'TestContext' => [
                    'name' => 'TestContext',
                    'methods' => [
                        ['name' => 'testAssertMethod', 'steps' => ['@Then a result should be displayed'], 'description' => 'Desc', 'example' => 'Example'],
                    ],
                ],
            ],
            'testAssertMethod',
            'step_wording',
            true,
            [],
        ];
        yield 'edge case with no' => [
            [
                'TestContext' => [
                    'name' => 'TestContext',
                    'methods' => [
                        ['name' => 'testAssertMethod', 'steps' => ['@Then no results should be displayed'], 'description' => 'Desc', 'example' => 'Example'],
                    ],
                ],
            ],
            'testAssertMethod',
            'step_wording',
            true,
            [],
        ];
        yield 'unnecessary regex' => [
            [
                'TestContext' => [
                    'name' => 'TestContext',
                    'methods' => [
                        ['name' => 'testMethod', 'steps' => ['@Given /^I wait for the batch job to finish$/'], 'description' => 'Desc', 'example' => 'Example'],
                    ],
                ],
            ],
            'testMethod',
            'regex_convertible',
            false,
            ['@Given /^I wait for the batch job to finish$/', '@Given I wait for the batch job to finish'],
        ];
        yield 'unnecessary regex with capture group' => [
            [
                'TestContext' => [
                    'name' => 'TestContext',
                    'methods' => [
                        ['name' => 'testMethod', 'steps' => ['@When /^I visit "([^"]*)"$/'], 'description' => 'Desc', 'example' => 'Example'],
                    ],
                ],
            ],
            'testMethod',
            'regex_convertible',
            false,
            ['@When /^I visit "([^"]*)"$/', '@When I visit :arg1'],
        ];
        yield 'turnip step passes regex check' => [
            [
                'TestContext' => [
                    'name' => 'TestContext',
                    'methods' => [
                        ['name' => 'testMethod', 'steps' => ['@Given I am at :path'], 'description' => 'Desc', 'example' => 'Example'],
                    ],
                ],
            ],
            'testMethod',
            'regex_convertible',
            true,
            [],
        ];
    }

    #[DataProvider('dataProviderRegexToTurnip')]
    public function testRegexToTurnip(string $step, ?string $expected): void
    {
        $this->assertSame($expected, regex_to_turnip($step));
    }

    public static function dataProviderRegexToTurnip(): \Iterator
    {
        yield 'plain literal no args' => [
            '@Given /^I wait for the batch job to finish$/',
            '@Given I wait for the batch job to finish',
        ];
        yield 'quoted capture group' => [
            '@When /^I visit "([^"]*)"$/',
            '@When I visit :arg1',
        ];
        yield 'multiple capture groups' => [
            '@Then /^I should see "([^"]*)" in the "([^"]*)" region$/',
            '@Then I should see :arg1 in the :arg2 region',
        ];
        yield 'dot-star capture' => [
            '@Given /^I am logged in as (.*)$/',
            '@Given I am logged in as :arg1',
        ];
        yield 'dot-plus capture' => [
            '@When /^I fill in "([^"]*)" with (.+)$/',
            '@When I fill in :arg1 with :arg2',
        ];
        yield 'numeric capture \\d+' => [
            '@Then /^I should see ([0-9]+) results$/',
            '@Then I should see :arg1 results',
        ];
        yield 'numeric capture d+' => [
            '@Then /^I should see (\d+) results$/',
            '@Then I should see :arg1 results',
        ];
        yield 'word capture \\w+' => [
            '@Given /^I am on the (\w+) page$/',
            '@Given I am on the :arg1 page',
        ];
        yield 'already turnip' => [
            '@Given I am at :path',
            null,
        ];
        yield 'alternation not convertible' => [
            '@When /^I (click|press) the button$/',
            null,
        ];
        yield 'optional group not convertible' => [
            '@Then /^I should see the (error )?message$/',
            null,
        ];
        yield 'complex character class not convertible' => [
            '@Given /^I enter ([a-z]+) in the field$/',
            null,
        ];
        yield 'single quote capture' => [
            "@When /^I click '([^']*)'$/",
            "@When I click :arg1",
        ];
        yield 'escaped slash in pattern' => [
            '@Given /^I visit the path \/admin\/config$/',
            '@Given I visit the path /admin/config',
        ];
    }

    public function testValidateFileExists(): void
    {
        mkdir(static::$tmp . '/tests/behat/features', 0777, true);
        file_put_contents(static::$tmp . '/tests/behat/features/test.feature', 'Feature: test');

        $info = [
            'TestContext' => [
                'name' => 'TestContext',
                'methods' => [
                    ['name' => 'testMethod', 'steps' => ['@Given something'], 'description' => 'Desc', 'example' => 'Example'],
                ],
            ],
        ];

        $results = validate($info, static::$tmp);
        $this->assertTrue($results['TestContext']['file']['pass']);
    }

    public function testValidateFileMissing(): void
    {
        $info = [
            'TestContext' => [
                'name' => 'TestContext',
                'methods' => [
                    ['name' => 'testMethod', 'steps' => ['@Given something'], 'description' => 'Desc', 'example' => 'Example'],
                ],
            ],
        ];

        $results = validate($info, static::$tmp);
        $this->assertFalse($results['TestContext']['file']['pass']);
        $this->assertSame('tests/behat/features/test.feature', $results['TestContext']['file']['path']);
    }

    #[DataProvider('dataProviderHasValidationErrors')]
    public function testHasValidationErrors(array $results, bool $expected): void
    {
        $this->assertSame($expected, has_validation_errors($results));
    }

    public static function dataProviderHasValidationErrors(): \Iterator
    {
        yield 'empty' => [
            [],
            false,
        ];
        yield 'all pass' => [
            [
                'TestContext' => [
                    'file' => ['pass' => true, 'path' => 'test.feature'],
                    'methods' => [
                        'testMethod' => [
                            'step_wording' => ['pass' => true, 'messages' => []],
                            'method_naming' => ['pass' => true, 'messages' => []],
                            'single_step' => ['pass' => true, 'messages' => []],
                            'has_example' => ['pass' => true, 'messages' => []],
                            'regex_convertible' => ['pass' => true, 'messages' => []],
                        ],
                    ],
                ],
            ],
            false,
        ];
        yield 'one failure' => [
            [
                'TestContext' => [
                    'file' => ['pass' => true, 'path' => 'test.feature'],
                    'methods' => [
                        'testMethod' => [
                            'step_wording' => ['pass' => false, 'messages' => ['Missing "I " in the step']],
                            'method_naming' => ['pass' => true, 'messages' => []],
                            'single_step' => ['pass' => true, 'messages' => []],
                            'has_example' => ['pass' => true, 'messages' => []],
                            'regex_convertible' => ['pass' => true, 'messages' => []],
                        ],
                    ],
                ],
            ],
            true,
        ];
    }

    public function testRenderValidationTreeAllPass(): void
    {
        $results = [
            'TestContext' => [
                'file' => ['pass' => true, 'path' => 'test.feature'],
                'methods' => [
                    'testMethod' => [
                        'step_wording' => ['pass' => true, 'messages' => []],
                        'method_naming' => ['pass' => true, 'messages' => []],
                        'single_step' => ['pass' => true, 'messages' => []],
                        'has_example' => ['pass' => true, 'messages' => []],
                        'regex_convertible' => ['pass' => true, 'messages' => []],
                    ],
                ],
            ],
        ];

        $output = render_validation_tree($results);
        $this->assertStringContainsString('TestContext', $output);
        $this->assertStringContainsString('■', $output);
        $this->assertStringContainsString('Example file present', $output);
        $this->assertStringContainsString('◆', $output);
        $this->assertStringContainsString('▲', $output);
        $this->assertStringContainsString('●', $output);
        $this->assertStringContainsString('✦', $output);
        $this->assertStringContainsString('⬢', $output);
        $this->assertStringContainsString('Validation warnings:', $output);
        $this->assertStringContainsString('Summary:', $output);
    }

    public function testRenderValidationTreeWithWarnings(): void
    {
        $results = [
            'TestContext' => [
                'file' => ['pass' => false, 'path' => 'tests/behat/features/test.feature'],
                'methods' => [
                    'clickOnElement' => [
                        'step_wording' => ['pass' => false, 'messages' => ['Missing "I " in the step']],
                        'method_naming' => ['pass' => false, 'messages' => ['Missing "Assert" in the method name']],
                        'single_step' => ['pass' => true, 'messages' => []],
                        'has_example' => ['pass' => false, 'messages' => ['Missing example']],
                        'regex_convertible' => ['pass' => true, 'messages' => []],
                    ],
                ],
            ],
        ];

        $output = render_validation_tree($results);
        $this->assertStringContainsString('TestContext', $output);
        $this->assertStringContainsString('□', $output);
        $this->assertStringContainsString('Example file absent', $output);
        $this->assertStringContainsString('tests/behat/features/test.feature', $output);
        $this->assertStringContainsString('◇', $output);
        $this->assertStringContainsString('Missing "I " in the step', $output);
        $this->assertStringContainsString('△', $output);
        $this->assertStringContainsString('Missing "Assert" in the method name', $output);
        $this->assertStringContainsString('●', $output);
        $this->assertStringContainsString('✧', $output);
        $this->assertStringContainsString('Missing example', $output);
    }

    public function testRenderValidationTreeMultipleClasses(): void
    {
        $results = [
            'FirstContext' => [
                'file' => ['pass' => true, 'path' => 'first.feature'],
                'methods' => [
                    'testMethod' => [
                        'step_wording' => ['pass' => true, 'messages' => []],
                        'method_naming' => ['pass' => true, 'messages' => []],
                        'single_step' => ['pass' => true, 'messages' => []],
                        'has_example' => ['pass' => true, 'messages' => []],
                        'regex_convertible' => ['pass' => true, 'messages' => []],
                    ],
                ],
            ],
            'SecondContext' => [
                'file' => ['pass' => false, 'path' => 'second.feature'],
                'methods' => [
                    'otherMethod' => [
                        'step_wording' => ['pass' => true, 'messages' => []],
                        'method_naming' => ['pass' => true, 'messages' => []],
                        'single_step' => ['pass' => false, 'messages' => ['Multiple steps found']],
                        'has_example' => ['pass' => true, 'messages' => []],
                        'regex_convertible' => ['pass' => true, 'messages' => []],
                    ],
                ],
            ],
        ];

        $output = render_validation_tree($results);
        $this->assertStringContainsString('FirstContext', $output);
        $this->assertStringContainsString('SecondContext', $output);
        $this->assertStringContainsString('○', $output);
        $this->assertStringContainsString('Multiple steps found', $output);
    }

    public function testRenderValidationTreeMultipleMessages(): void
    {
        $results = [
            'TestContext' => [
                'file' => ['pass' => true, 'path' => 'test.feature'],
                'methods' => [
                    'testMethod' => [
                        'step_wording' => ['pass' => false, 'messages' => ['Missing "should" in the step', 'Missing "the", "a" or "no" in the step']],
                        'method_naming' => ['pass' => true, 'messages' => []],
                        'single_step' => ['pass' => true, 'messages' => []],
                        'has_example' => ['pass' => true, 'messages' => []],
                        'regex_convertible' => ['pass' => true, 'messages' => []],
                    ],
                ],
            ],
        ];

        $output = render_validation_tree($results);
        $this->assertStringContainsString('Missing "should" in the step', $output);
        $this->assertStringContainsString('Missing "the", "a" or "no" in the step', $output);
    }

    public function testRenderValidationTreeContainsAnsiColors(): void
    {
        $results = [
            'TestContext' => [
                'file' => ['pass' => true, 'path' => 'test.feature'],
                'methods' => [
                    'testMethod' => [
                        'step_wording' => ['pass' => true, 'messages' => []],
                        'method_naming' => ['pass' => false, 'messages' => ['Missing "Assert"']],
                        'single_step' => ['pass' => true, 'messages' => []],
                        'has_example' => ['pass' => true, 'messages' => []],
                        'regex_convertible' => ['pass' => true, 'messages' => []],
                    ],
                ],
            ],
        ];

        $output = render_validation_tree($results);
        // Green for pass.
        $this->assertStringContainsString("\033[32m", $output);
        // Yellow for warn.
        $this->assertStringContainsString("\033[33m", $output);
        // Dim for detail.
        $this->assertStringContainsString("\033[2m", $output);
    }

    public function testRenderValidationTreeStructure(): void
    {
        $results = [
            'TestContext' => [
                'file' => ['pass' => true, 'path' => 'test.feature'],
                'methods' => [
                    'testMethod' => [
                        'step_wording' => ['pass' => true, 'messages' => []],
                        'method_naming' => ['pass' => true, 'messages' => []],
                        'single_step' => ['pass' => true, 'messages' => []],
                        'has_example' => ['pass' => true, 'messages' => []],
                        'regex_convertible' => ['pass' => true, 'messages' => []],
                    ],
                ],
            ],
        ];

        $output = render_validation_tree($results);
        // Check tree structure characters.
        $this->assertStringContainsString('├── ', $output);
        $this->assertStringContainsString('└── ', $output);
    }

    #[DataProvider('dataProviderReplaceContent')]
    public function testReplaceContent(
        string $haystack,
        string $start,
        string $end,
        string $replacement,
        string $expected,
        ?string $exception = null,
    ): void {
        if ($exception) {
            $this->expectException(\Exception::class);
            $this->expectExceptionMessage($exception);
        }

        $actual = replace_content($haystack, $start, $end, $replacement);
        $this->assertEquals($expected, $actual);
    }

    public static function dataProviderReplaceContent(): \Iterator
    {
        yield 'basic replacement' => [
            'This is a test string with START some content END in it.',
            'START',
            'END',
            ' new content ',
            "This is a test string with START\n new content \nEND in it.",
        ];
        yield 'multiline content' => [
            "Line 1\nSTART\nsome content\nmore content\nEND\nLine 3",
            "START",
            "END",
            "\nnew content\n",
            "Line 1\nSTART\n\nnew content\n\nEND\nLine 3",
        ];
        yield 'replacement with special characters' => [
            'Content with START $pecial ch@rs END here',
            'START',
            'END',
            ' $p3c!al r3pl@cement ',
            "Content with START\n \$p3c!al r3pl@cement \nEND here",
        ];
        yield 'start and end with regex characters' => [
            'Content with [START] regex.chars* [END] here',
            '[START]',
            '[END]',
            ' escaped content ',
            "Content with [START]\n escaped content \n[END] here",
        ];
        yield 'error - start not found' => [
            'Content without markers',
            'START',
            'END',
            'replacement',
            '',
            'Start not found in the haystack',
        ];
        yield 'error - end not found' => [
            'Content with START but no end',
            'START',
            'END',
            'replacement',
            '',
            'End not found in the haystack',
        ];
        yield 'error - start after end' => [
            'Content with END before START',
            'START',
            'END',
            'replacement',
            '',
            'Start is after the end',
        ];
        yield 'adjacent markers' => [
            'Content with STARTEND together',
            'START',
            'END',
            ' replacement ',
            "Content with START\n replacement \nEND together",
        ];
        yield 'empty replacement' => [
            'Content with START content to remove END here',
            'START',
            'END',
            '',
            "Content with START\n\nEND here",
        ];
    }

    #[DataProvider('dataProviderParseClassComment')]
    public function testParseClassComment(string $class_name, string $comment, array $expected, ?string $exception = null): void
    {
        if ($exception) {
            $this->expectException(\Exception::class);
            $this->expectExceptionMessage($exception);
        }

        $actual = parse_class_comment($class_name, $comment);
        $this->assertEquals($expected, $actual);
    }

    public static function dataProviderParseClassComment(): \Iterator
    {
        yield 'valid comment' => [
            'TestContext',
            <<<'EOD'
/**
 * Test context description.
 *
 * Additional information about the context.
 */
EOD,
            [
                'description' => 'Test context description.',
                'description_full' => 'Test context description.' . PHP_EOL . PHP_EOL . 'Additional information about the context.',
            ],
        ];
        yield 'single line comment' => [
            'TestContext',
            <<<'EOD'
/**
 * Test context description.
 */
EOD,
            [
                'description' => 'Test context description.',
                'description_full' => 'Test context description.',
            ],
        ];
        yield 'with code blocks' => [
            'TestContext',
            <<<'EOD'
/**
 * Test context with `code` blocks.
 *
 * Example: `some code`
 */
EOD,
            [
                'description' => 'Test context with `code` blocks.',
                'description_full' => 'Test context with `code` blocks.' . PHP_EOL . PHP_EOL . 'Example: `some code`',
            ],
        ];
        yield 'empty comment error' => [
            'TestContext',
            '',
            [],
            'Class comment for TestContext is empty',
        ];
        yield 'comment without content error' => [
            'TestContext',
            <<<'EOD'
/**
 *
 */
EOD,
            [],
            'Class comment for TestContext is empty',
        ];
        yield 'class as description error' => [
            'TestContext',
            <<<'EOD'
/**
 * Class TestContext for testing purposes.
 */
EOD,
            [],
            'Class comment should have a descriptive content for TestContext',
        ];
        yield 'unclosed code block error' => [
            'TestContext',
            <<<'EOD'
/**
 * Test context with `code blocks.
 */
EOD,
            [],
            'Class inline code block is not closed for TestContext',
        ];
        yield 'with @code block' => [
            'TestContext',
            <<<'EOD'
/**
 * Test context description.
 *
 * @code
 * Given I am on the homepage
 * When I click "Submit"
 * @endcode
 */
EOD,
            [
                'description' => 'Test context description.',
                'description_full' => 'Test context description.' . PHP_EOL . PHP_EOL . '@code' . PHP_EOL . 'Given I am on the homepage' . PHP_EOL . 'When I click "Submit"' . PHP_EOL . '@endcode',
            ],
        ];
        yield 'only whitespace lines - empty after processing' => [
            'TestContext',
            "/**\n*/",
            [],
            'Class comment for TestContext is empty',
        ];
    }

    /**
     * Test the extract_info function with actual reflection.
     */
    #[DataProvider('dataProviderExtractInfo')]
    public function testExtractInfo(
        array $class_names,
        array $exclude,
        array $expected_class_names,
    ): void {
        $basePath = static::$tmp;
        $contextDir = $basePath . DIRECTORY_SEPARATOR . 'src';
        if (!is_dir($contextDir)) {
            mkdir($contextDir, 0777, true);
        }

        // Copy fixture files to the context directory.
        $fixturesDir = __DIR__ . '/../fixtures/docs';
        foreach ($class_names as $className) {
            $fixtureFile = $fixturesDir . DIRECTORY_SEPARATOR . $className . '.php';
            if (file_exists($fixtureFile)) {
                copy($fixtureFile, $contextDir . DIRECTORY_SEPARATOR . $className . '.php');
            }
        }

        $result = extract_info($contextDir, $exclude, $basePath, 'Drupal\\DrupalExtension\\Tests\\Fixtures');

        foreach ($expected_class_names as $expectedClass) {
            if (!in_array($expectedClass, $exclude, true)) {
                $this->assertArrayHasKey($expectedClass, $result);
                $this->assertEquals($expectedClass, $result[$expectedClass]['name']);
            } else {
                $this->assertArrayNotHasKey($expectedClass, $result);
            }
        }
    }

    public static function dataProviderExtractInfo(): \Iterator
    {
        yield 'single context with step' => [
            ['SampleContext'],
            [],
            ['SampleContext'],
        ];
        yield 'multiple contexts with steps' => [
            ['FirstContext', 'SecondContext'],
            [],
            ['FirstContext', 'SecondContext'],
        ];
        yield 'with exclude' => [
            ['FirstContext', 'SecondContext'],
            ['SecondContext'],
            ['FirstContext'],
        ];
    }

    /**
     * Test extract_info with context having multiple methods (tests sorting).
     */
    public function testExtractInfoMultipleMethods(): void
    {
        $basePath = static::$tmp;
        $contextDir = $basePath . DIRECTORY_SEPARATOR . 'src';
        if (!is_dir($contextDir)) {
            mkdir($contextDir, 0777, true);
        }

        $className = 'MultiMethodContext';
        $fixturesDir = __DIR__ . '/../fixtures/docs';
        copy($fixturesDir . DIRECTORY_SEPARATOR . $className . '.php', $contextDir . DIRECTORY_SEPARATOR . $className . '.php');

        $result = extract_info($contextDir, [], $basePath, 'Drupal\\DrupalExtension\\Tests\\Fixtures');

        $this->assertArrayHasKey($className, $result);
        $this->assertIsArray($result[$className]['methods']);
        $this->assertCount(3, $result[$className]['methods']);

        // Check order: Given, When, Then.
        $this->assertArrayHasKey('steps', $result[$className]['methods'][0]);
        $this->assertStringContainsString('@Given', $result[$className]['methods'][0]['steps'][0]);
        $this->assertArrayHasKey('steps', $result[$className]['methods'][1]);
        $this->assertStringContainsString('@When', $result[$className]['methods'][1]['steps'][0]);
        $this->assertArrayHasKey('steps', $result[$className]['methods'][2]);
        $this->assertStringContainsString('@Then', $result[$className]['methods'][2]['steps'][0]);
    }

    /**
     * Test extract_info with empty class comment.
     */
    public function testExtractInfoEmptyClassComment(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Class comment for EmptyCommentContext is empty');

        $basePath = static::$tmp;
        $contextDir = $basePath . DIRECTORY_SEPARATOR . 'src';
        if (!is_dir($contextDir)) {
            mkdir($contextDir, 0777, true);
        }

        $className = 'EmptyCommentContext';
        $fixturesDir = __DIR__ . '/../fixtures/docs';
        copy($fixturesDir . DIRECTORY_SEPARATOR . $className . '.php', $contextDir . DIRECTORY_SEPARATOR . $className . '.php');

        extract_info($contextDir, [], $basePath, 'Drupal\\DrupalExtension\\Tests\\Fixtures');
    }

    /**
     * Test extract_info with non-existent directory.
     */
    public function testExtractInfoNonExistentDirectory(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Context directory');

        extract_info('/non/existent/directory', [], static::$tmp);
    }

    /**
     * Test extract_info excludes classes without step definitions.
     */
    public function testExtractInfoNoStepAnnotations(): void
    {
        $basePath = static::$tmp;
        $contextDir = $basePath . DIRECTORY_SEPARATOR . 'src';
        if (!is_dir($contextDir)) {
            mkdir($contextDir, 0777, true);
        }

        $className = 'NoStepAnnotationContext';
        $fixturesDir = __DIR__ . '/../fixtures/docs';
        copy($fixturesDir . DIRECTORY_SEPARATOR . $className . '.php', $contextDir . DIRECTORY_SEPARATOR . $className . '.php');

        $result = extract_info($contextDir, [], $basePath, 'Drupal\\DrupalExtension\\Tests\\Fixtures');

        // Context should not appear since it has no step methods.
        $this->assertArrayNotHasKey($className, $result);
    }

    /**
     * Test find_source_file function.
     */
    public function testFindSourceFile(): void
    {
        $basePath = static::$tmp;

        // Test with file in Context directory.
        $contextDir = $basePath . '/src/Drupal/DrupalExtension/Context';
        mkdir($contextDir, 0777, true);
        file_put_contents($contextDir . '/TestContext.php', '<?php');

        $result = find_source_file('TestContext', $basePath);
        $this->assertEquals('src/Drupal/DrupalExtension/Context/TestContext.php', $result);
    }

    /**
     * Test find_source_file with fallback to src root.
     */
    public function testFindSourceFileFallback(): void
    {
        $basePath = static::$tmp;

        $srcDir = $basePath . '/src';
        if (!is_dir($srcDir)) {
            mkdir($srcDir, 0777, true);
        }
        file_put_contents($srcDir . '/TestContext.php', '<?php');

        $result = find_source_file('TestContext', $basePath);
        $this->assertEquals('src/TestContext.php', $result);
    }

    /**
     * Test find_source_file returns null when not found.
     */
    public function testFindSourceFileNotFound(): void
    {
        $result = find_source_file('NonExistentContext', static::$tmp);
        $this->assertNull($result);
    }

    public function testExtractInfoSkipsInterfacesAndAbstract(): void
    {
        $basePath = static::$tmp;
        $contextDir = $basePath . DIRECTORY_SEPARATOR . 'src';
        if (!is_dir($contextDir)) {
            mkdir($contextDir, 0777, true);
        }

        $fixturesDir = __DIR__ . '/../fixtures/docs';
        foreach (['InterfaceContext', 'AbstractContext', 'SampleContext'] as $className) {
            copy($fixturesDir . DIRECTORY_SEPARATOR . $className . '.php', $contextDir . DIRECTORY_SEPARATOR . $className . '.php');
        }

        $result = extract_info($contextDir, [], $basePath, 'Drupal\\DrupalExtension\\Tests\\Fixtures');

        $this->assertArrayNotHasKey('InterfaceContext', $result);
        $this->assertArrayNotHasKey('AbstractContext', $result);
        $this->assertArrayHasKey('SampleContext', $result);
    }

    public function testExtractInfoSkipsInheritedMethods(): void
    {
        $basePath = static::$tmp;
        $contextDir = $basePath . DIRECTORY_SEPARATOR . 'src';
        if (!is_dir($contextDir)) {
            mkdir($contextDir, 0777, true);
        }

        $fixturesDir = __DIR__ . '/../fixtures/docs';
        copy($fixturesDir . DIRECTORY_SEPARATOR . 'InheritedContext.php', $contextDir . DIRECTORY_SEPARATOR . 'InheritedContext.php');

        $result = extract_info($contextDir, [], $basePath, 'Drupal\\DrupalExtension\\Tests\\Fixtures');

        $this->assertArrayHasKey('InheritedContext', $result);
        // Should only have its own method, not the inherited one.
        $this->assertCount(1, $result['InheritedContext']['methods']);
        $this->assertSame('inheritedAssertOwn', $result['InheritedContext']['methods'][0]['name']);
    }

    public function testExtractInfoSkipsNonExistentClasses(): void
    {
        $basePath = static::$tmp;
        $contextDir = $basePath . DIRECTORY_SEPARATOR . 'src';
        if (!is_dir($contextDir)) {
            mkdir($contextDir, 0777, true);
        }

        // Create a PHP file whose class doesn't match the namespace.
        file_put_contents($contextDir . DIRECTORY_SEPARATOR . 'NonExistent.php', '<?php');

        $result = extract_info($contextDir, [], $basePath, 'Drupal\\DrupalExtension\\Tests\\Fixtures');

        $this->assertArrayNotHasKey('NonExistent', $result);
    }

    public function testRenderInfoWithListDescription(): void
    {
        $basePath = static::$tmp;
        $srcDir = $basePath . '/src';
        if (!is_dir($srcDir)) {
            mkdir($srcDir, 0777, true);
        }
        file_put_contents($srcDir . '/ListDescContext.php', '<?php');

        $featureDir = $basePath . '/tests/behat/features';
        if (!is_dir($featureDir)) {
            mkdir($featureDir, 0777, true);
        }
        file_put_contents($featureDir . '/list_desc.feature', 'Feature: Test');

        $info = [
            'ListDescContext' => [
                'name' => 'ListDescContext',
                'name_contextual' => 'ListDescContext',
                'description' => 'Context with list.',
                'description_full' => "Context with list.\n\n- First item\n- Second item",
                'methods' => [
                    ['steps' => ['@Then the list should be visible'], 'description' => 'Step', 'example' => 'Then the list should be visible'],
                ],
            ],
        ];

        $output = render_info($info, $basePath);

        $this->assertStringContainsString('- First item', $output);
        $this->assertStringContainsString('- Second item', $output);
    }

    public function testWriteValidationLogsWithWarnings(): void
    {
        $logDir = static::$tmp . '/logs';

        $results = [
            'TestContext' => [
                'file' => ['pass' => true, 'path' => 'test.feature'],
                'methods' => [
                    'testMethod' => [
                        'step_wording' => ['pass' => false, 'messages' => ['Missing "I "']],
                        'method_naming' => ['pass' => true, 'messages' => []],
                        'single_step' => ['pass' => true, 'messages' => []],
                        'has_example' => ['pass' => true, 'messages' => []],
                        'regex_convertible' => ['pass' => true, 'messages' => []],
                    ],
                ],
            ],
        ];

        $treeOutput = render_validation_tree($results);
        write_validation_logs($treeOutput, $logDir);

        $this->assertFileExists($logDir . '/validation-summary.txt');
        $this->assertFileExists($logDir . '/validation-details.txt');

        $summary = file_get_contents($logDir . '/validation-summary.txt');
        $details = file_get_contents($logDir . '/validation-details.txt');

        // Summary should contain the summary block without ANSI.
        $this->assertStringContainsString('Summary:', $summary);
        $this->assertStringNotContainsString("\033[", $summary);

        // Details should contain the per-context tree without ANSI.
        $this->assertStringContainsString('TestContext', $details);
        $this->assertStringNotContainsString("\033[", $details);
    }

    public function testWriteValidationLogsEmpty(): void
    {
        $logDir = static::$tmp . '/logs-empty';

        write_validation_logs('', $logDir);

        $this->assertFileExists($logDir . '/validation-summary.txt');
        $this->assertFileExists($logDir . '/validation-details.txt');

        $summary = file_get_contents($logDir . '/validation-summary.txt');
        $this->assertSame('No validation warnings.' . PHP_EOL, $summary);

        $details = file_get_contents($logDir . '/validation-details.txt');
        $this->assertSame('', $details);
    }

    public function testWriteValidationLogsCreatesDirectory(): void
    {
        $logDir = static::$tmp . '/nested/logs/dir';

        write_validation_logs('', $logDir);

        $this->assertDirectoryExists($logDir);
        $this->assertFileExists($logDir . '/validation-summary.txt');
    }

    public function testRegexToTurnipWithRemainingMetachars(): void
    {
        // Pattern with metachar in literal text (not in a capture group).
        $this->assertNull(regex_to_turnip('@Given /^I match .* something$/'));
        $this->assertNull(regex_to_turnip('@Given /^I match [abc] something$/'));
        $this->assertNull(regex_to_turnip('@Given /^I match something+$/'));
    }
}
