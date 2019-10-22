<?php

use Behat\Gherkin\Node\PyStringNode;
use Behat\Gherkin\Node\TableNode;
use Drupal\DrupalExtension\Context\RawDrupalContext;
use Drupal\DrupalExtension\Hook\Scope\BeforeNodeCreateScope;
use Drupal\DrupalExtension\Hook\Scope\EntityScope;
use Drupal\DrupalExtension\TagTrait;
use Symfony\Component\Process\PhpExecutableFinder;
use Symfony\Component\Process\Process;

/**
 * Features context for testing the Drupal Extension.
 *
 * @todo we are duplicating code from Behat's FeatureContext here for the
 * purposes of testing since we can't easily run that as a context due to naming
 * conflicts.
 */
class FeatureContext extends RawDrupalContext {

    use TagTrait;

  /**
   * Hook into node creation to test `@beforeNodeCreate`
   *
   * @beforeNodeCreate
   */
  public static function alterNodeParameters(BeforeNodeCreateScope $scope) {
    call_user_func('parent::alterNodeParameters', $scope);
    // @see `features/api.feature`
    // Change 'published on' to the expected 'created'.
    $node = $scope->getEntity();
    if (isset($node->{"published on"})) {
      $node->created = $node->{"published on"};
      unset($node->{"published on"});
    }
  }

  /**
   * Hook into term creation to test `@beforeTermCreate`
   *
   * @beforeTermCreate
   */
  public static function alterTermParameters(EntityScope $scope) {
    // @see `features/api.feature`
    // Change 'Label' to expected 'name'.
    $term = $scope->getEntity();
    if (isset($term->{'Label'})) {
      $term->name = $term->{'Label'};
      unset($term->{'Label'});
    }
  }

  /**
   * Hook into user creation to test `@beforeUserCreate`
   *
   * @beforeUserCreate
   */
  public static function alterUserParameters(EntityScope $scope) {
    // @see `features/api.feature`
    // Concatenate 'First name' and 'Last name' to form user name.
    $user = $scope->getEntity();
    if (isset($user->{"First name"}) && isset($user->{"Last name"})) {
      $user->name = $user->{"First name"} . ' ' . $user->{"Last name"};
      unset($user->{"First name"}, $user->{"Last name"});
    }
    // Transform custom 'E-mail' to 'mail'.
    if (isset($user->{"E-mail"})) {
      $user->mail = $user->{"E-mail"};
      unset($user->{"E-mail"});
    }
  }

  /**
   * Test that a node is returned after node create.
   *
   * @afterNodeCreate
   */
  public static function afterNodeCreate(EntityScope $scope) {
    if (!$node = $scope->getEntity()) {
      throw new \Exception('Failed to find a node in @afterNodeCreate hook.');
    }
  }

  /**
   * Test that a term is returned after term create.
   *
   * @afterTermCreate
   */
  public static function afterTermCreate(EntityScope $scope) {
    if (!$term = $scope->getEntity()) {
      throw new \Exception('Failed to find a term in @afterTermCreate hook.');
    }
  }

  /**
   * Test that a user is returned after user create.
   *
   * @afterUserCreate
   */
  public static function afterUserCreate(EntityScope $scope) {
    if (!$user = $scope->getEntity()) {
      throw new \Exception('Failed to find a user in @afterUserCreate hook.');
    }
  }

  /**
   * Transforms long address field columns into shorter aliases.
   *
   * This is used in field_handlers.feature for testing if lengthy field:column
   * combinations can be shortened to more human friendly aliases.
   *
   * @Transform table:name,mail,street,city,postcode,country
   */
  public function castUsersTable(TableNode $user_table) {
    $aliases = array(
      'country' => 'field_post_address:country',
      'city' => 'field_post_address:locality',
      'street' => 'field_post_address:thoroughfare',
      'postcode' => 'field_post_address:postal_code',
    );

    // The first row of the table contains the field names.
    $table = $user_table->getTable();
    reset($table);
    $first_row = key($table);

    // Replace the aliased field names with the actual ones.
    foreach ($table[$first_row] as $key => $alias) {
      if (array_key_exists($alias, $aliases)) {
        $table[$first_row][$key] = $aliases[$alias];
      }
    }

    return new TableNode($table);
  }

  /**
   * Transforms human readable field names into machine names.
   *
   * This is used in field_handlers.feature for testing if human readable names
   * can be used instead of machine names in tests.
   *
   * @param TableNode $post_table
   *   The original table.
   *
   * @return TableNode
   *   The transformed table.
   *
   * @Transform rowtable:title,body,reference,date,links,select,address
   */
  public function transformPostContentTable(TableNode $post_table) {
    $aliases = array(
      'reference' => 'field_post_reference',
      'date' => 'field_post_date',
      'links' => 'field_post_links',
      'select' => 'field_post_select',
      'address' => 'field_post_address',
    );

    $table = $post_table->getTable();
    array_walk($table, function (&$row) use ($aliases) {
      // The first column of the row contains the field names. Replace the
      // human readable field name with the machine name if it exists.
      if (array_key_exists($row[0], $aliases)) {
        $row[0] = $aliases[$row[0]];
      }
    });

    return new TableNode($table);
  }

  /**
   * Creates and authenticates a user with the given username and password.
   *
   * In Drupal 8 it is possible to register a user without an e-mail address,
   * using only a username and password.
   *
   * This step definition is intended to test if users that are registered in
   * one context (in this case FeatureContext) can be accessed in other
   * contexts.
   *
   * See the scenario 'Logging in as a user without an e-mail address' in
   * d8.feature.
   *
   * @Given I am logged in as a user with name :name and password :password
   */
  public function assertAuthenticatedByUsernameAndPassword($name, $password) {
    $user = (object) [
      'name' => $name,
      'pass' => $password,
    ];
    $this->userCreate($user);
    $this->login($user);
  }

    /**
     * Verifies a user is logged in on the backend.
     *
     * @Then I should be logged in on the backend
     * @Then I am logged in on the backend
     */
    public function assertBackendLogin()
    {
        if (!$user = $this->getUserManager()->getCurrentUser()) {
            throw new \LogicException('No current user in the user manager.');
        }
        if (!$account = \Drupal::entityTypeManager()->getStorage('user')->load($user->uid)) {
            throw new \LogicException('No user found in the system.');
        }
        if (!$account->id()) {
            throw new \LogicException('Current user is anonymous.');
        }
        if ($account->id() != \Drupal::currentUser()->id()) {
            throw new \LogicException('User logged in on the backend does not match current user.');
        }
    }

    /**
     * Verifies there is no user logged in on the backend.
     *
     * @Then I should be logged out on the backend
     */
    public function assertBackendLoggedOut()
    {
        if ($this->getUserManager()->getCurrentUser()) {
            throw new \LogicException('User is still logged in in the manager.');
        }
        if (!\Drupal::currentUser()->isAnonymous()) {
            throw new \LogicException('User is still logged in on the backend.');
        }
    }

  /**
   * From here down is the Behat FeatureContext.
   *
   * @defgroup Behat FeatureContext
   * @{
   */

    /**
     * @var string
     */
    private $phpBin;
    /**
     * @var Process
     */
    private $process;
    /**
     * @var string
     */
    private $workingDir;

    /**
     * Cleans test folders in the temporary directory.
     *
     * @BeforeSuite
     * @AfterSuite
     */
    public static function cleanTestFolders()
    {
        if (is_dir($dir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'behat')) {
            self::clearDirectory($dir);
        }
    }

    /**
     * Prepares test folders in the temporary directory.
     *
     * @BeforeScenario
     */
    public function prepareTestFolders()
    {
        do {
            $random_name = md5((int) microtime(true) * rand(0, 100000));
            $dir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'behat' . DIRECTORY_SEPARATOR . $random_name;
        } while (is_dir($dir));

        mkdir($dir . '/features/bootstrap/i18n', 0777, true);

        $phpFinder = new PhpExecutableFinder();
        if (false === $php = $phpFinder->find()) {
            throw new \RuntimeException('Unable to find the PHP executable.');
        }
        $this->workingDir = $dir;
        $this->phpBin = $php;
        $this->process = new Process(null);
    }

    /**
     * Creates a file with specified name and context in current workdir.
     *
     * @Given /^(?:there is )?a file named "([^"]*)" with:$/
     *
     * @param   string       $filename name of the file (relative path)
     * @param   PyStringNode $content  PyString string instance
     */
    public function aFileNamedWith($filename, PyStringNode $content)
    {
        $content = strtr((string) $content, array("'''" => '"""'));
        $this->createFile($this->workingDir . '/' . $filename, $content);
    }

    /**
     * Moves user to the specified path.
     *
     * @Given /^I am in the "([^"]*)" path$/
     *
     * @param   string $path
     */
    public function iAmInThePath($path)
    {
        $this->moveToNewPath($path);
    }

    /**
     * Checks whether a file at provided path exists.
     *
     * @Given /^file "([^"]*)" should exist$/
     *
     * @param   string $path
     */
    public function fileShouldExist($path)
    {
        PHPUnit_Framework_Assert::assertFileExists($this->workingDir . DIRECTORY_SEPARATOR . $path);
    }

    /**
     * Sets specified ENV variable
     *
     * @When /^"BEHAT_PARAMS" environment variable is set to:$/
     *
     * @param PyStringNode $value
     */
    public function iSetEnvironmentVariable(PyStringNode $value)
    {
        $this->process->setEnv(array('BEHAT_PARAMS' => (string) $value));
    }

    /**
     * Runs behat command with provided parameters
     *
     * @When /^I run "behat(?: ((?:\"|[^"])*))?"$/
     *
     * @param   string $argumentsString
     */
    public function iRunBehat($argumentsString = '')
    {
        $argumentsString = strtr($argumentsString, array('\'' => '"'));

        $this->process->setWorkingDirectory($this->workingDir);
        $this->process->setCommandLine(
            sprintf(
                '%s %s %s %s',
                $this->phpBin,
                escapeshellarg(BEHAT_BIN_PATH),
                $argumentsString,
                strtr('--format-settings=\'{"timer": false}\'', array('\'' => '"', '"' => '\"'))
            )
        );
        $this->process->start();
        $this->process->wait();
    }

    /**
     * Checks whether previously ran command passes|fails with provided output.
     *
     * @Then /^it should (fail|pass) with:$/
     *
     * @param   string       $success "fail" or "pass"
     * @param   PyStringNode $text    PyString text instance
     */
    public function itShouldPassWith($success, PyStringNode $text)
    {
        $this->itShouldFail($success);
        $this->theOutputShouldContain($text);
    }

    /**
     * Checks whether specified file exists and contains specified string.
     *
     * @Then /^"([^"]*)" file should contain:$/
     *
     * @param   string       $path file path
     * @param   PyStringNode $text file content
     */
    public function fileShouldContain($path, PyStringNode $text)
    {
        $path = $this->workingDir . '/' . $path;
        PHPUnit_Framework_Assert::assertFileExists($path);

        $fileContent = trim(file_get_contents($path));
        // Normalize the line endings in the output
        if ("\n" !== PHP_EOL) {
            $fileContent = str_replace(PHP_EOL, "\n", $fileContent);
        }

        PHPUnit_Framework_Assert::assertEquals($this->getExpectedOutput($text), $fileContent);
    }

    /**
     * Checks whether last command output contains provided string.
     *
     * @Then the output should contain:
     *
     * @param   PyStringNode $text PyString text instance
     */
    public function theOutputShouldContain(PyStringNode $text)
    {
        PHPUnit_Framework_Assert::assertContains($this->getExpectedOutput($text), $this->getOutput());
    }

    private function getExpectedOutput(PyStringNode $expectedText)
    {
        $text = strtr($expectedText, array('\'\'\'' => '"""', '%%TMP_DIR%%' => sys_get_temp_dir() . DIRECTORY_SEPARATOR));

        // windows path fix
        if ('/' !== DIRECTORY_SEPARATOR) {
            $text = preg_replace_callback(
                '/ features\/[^\n ]+/', function ($matches) {
                    return str_replace('/', DIRECTORY_SEPARATOR, $matches[0]);
                }, $text
            );
            $text = preg_replace_callback(
                '/\<span class\="path"\>features\/[^\<]+/', function ($matches) {
                    return str_replace('/', DIRECTORY_SEPARATOR, $matches[0]);
                }, $text
            );
            $text = preg_replace_callback(
                '/\+[fd] [^ ]+/', function ($matches) {
                    return str_replace('/', DIRECTORY_SEPARATOR, $matches[0]);
                }, $text
            );
        }

        return $text;
    }

    /**
     * Checks whether previously ran command failed|passed.
     *
     * @Then /^it should (fail|pass)$/
     *
     * @param   string $success "fail" or "pass"
     */
    public function itShouldFail($success)
    {
        if ('fail' === $success) {
            if (0 === $this->getExitCode()) {
                echo 'Actual output:' . PHP_EOL . PHP_EOL . $this->getOutput();
            }

            PHPUnit_Framework_Assert::assertNotEquals(0, $this->getExitCode());
        } else {
            if (0 !== $this->getExitCode()) {
                echo 'Actual output:' . PHP_EOL . PHP_EOL . $this->getOutput();
            }

            PHPUnit_Framework_Assert::assertEquals(0, $this->getExitCode());
        }
    }

    /**
     * Checks if the current scenario or feature has the given tag.
     *
     * @Then the :tag tag should be present
     *
     * @param string $tag
     *   The tag to check.
     */
    public function shouldHaveTag($tag)
    {
        if (!$this->hasTag($tag)) {
            throw new \Exception("Expected tag $tag was not found in the scenario or feature.");
        }
    }

    /**
     * Checks if the current scenario or feature does not have the given tag.
     *
     * @Then the :tag tag should not be present
     *
     * @param string $tag
     *   The tag to check.
     */
    public function shouldNotHaveTag($tag)
    {
        if ($this->hasTag($tag)) {
            throw new \Exception("Expected tag $tag was found in the scenario or feature.");
        }
    }

    private function getExitCode()
    {
        return $this->process->getExitCode();
    }

    private function getOutput()
    {
        $output = $this->process->getErrorOutput() . $this->process->getOutput();

        // Normalize the line endings in the output
        if ("\n" !== PHP_EOL) {
            $output = str_replace(PHP_EOL, "\n", $output);
        }

        // Replace wrong warning message of HHVM
        $output = str_replace('Notice: Undefined index: ', 'Notice: Undefined offset: ', $output);

        return trim(preg_replace("/ +$/m", '', $output));
    }

    private function createFile($filename, $content)
    {
        $path = dirname($filename);
        if (!is_dir($path)) {
            mkdir($path, 0777, true);
        }

        file_put_contents($filename, $content);
    }

    private function moveToNewPath($path)
    {
        $newWorkingDir = $this->workingDir .'/' . $path;
        if (!file_exists($newWorkingDir)) {
            mkdir($newWorkingDir, 0777, true);
        }

        $this->workingDir = $newWorkingDir;
    }

    private static function clearDirectory($path)
    {
        $files = scandir($path);
        array_shift($files);
        array_shift($files);

        foreach ($files as $file) {
            $file = $path . DIRECTORY_SEPARATOR . $file;
            if (is_dir($file)) {
                self::clearDirectory($file);
            } else {
                unlink($file);
            }
        }

        rmdir($path);
    }

  /**
   * @} End of defgroup Behat FeatureContext.
   */

}
