<?php

declare(strict_types=1);

use Behat\Behat\Hook\Scope\AfterFeatureScope;
use Behat\Behat\Hook\Scope\BeforeScenarioScope;
use Behat\Gherkin\Node\TableNode;
use Behat\Hook\AfterFeature;
use Behat\Hook\BeforeScenario;
use Behat\Mink\Exception\ExpectationException;
use Drupal\Core\Database\Database;
use Drupal\DrupalExtension\Context\RawDrupalContext;
use Drupal\DrupalExtension\Hook\Scope\BeforeNodeCreateScope;
use Drupal\DrupalExtension\Hook\Scope\EntityScope;
use Drupal\DrupalExtension\TagTrait;

/**
 * Features context for testing the Drupal Extension.
 *
 * @phpcs:disable PSR1.Classes.ClassDeclaration.MissingNamespace
 */
class FeatureContext extends RawDrupalContext {

  use TagTrait;

  /**
   * Stop Mink sessions before scenarios that will spawn sub-processes.
   *
   * When a @javascript scenario runs in the parent process, Mink keeps the
   * Selenium2/Chrome connection open (via resetSessions()). This causes
   * child processes to hang when they try to establish their own connection.
   * This hook ensures all sessions are properly stopped before sub-process
   * scenarios run.
   */
  #[BeforeScenario]
  public function testStopSessionsBeforeSubProcess(BeforeScenarioScope $scope): void {
    $hasTraitTag = (bool) array_filter($scope->getScenario()->getTags(), fn(string $tag): bool => str_starts_with($tag, 'javascript'));

    // Stop all Mink sessions before sub-process scenarios to prevent
    // connection interference between parent and child processes.
    // @see \Behat\MinkExtension\Listener\SessionsListener::prepareDefaultMinkSession().
    if ($hasTraitTag) {
      $this->getMink()->stopSessions();
    }
  }

  /**
   * Sleep for the given number of seconds.
   *
   * @code
   * When sleep for 5 seconds
   * @endcode
   *
   * @When sleep for :seconds second(s)
   */
  public function testSleepForSeconds(int|string $seconds): void {
    sleep((int) $seconds);
  }

  /**
   * Clean watchdog after feature with an error.
   */
  #[AfterFeature('@errorcleanup')]
  public static function testClearWatchdog(AfterFeatureScope $scope): void {
    $database = Database::getConnection();
    if ($database->schema()->tableExists('watchdog')) {
      $database->truncate('watchdog')->execute();
    }
  }

  /**
   * Clear watchdog table.
   *
   * @Given the watchdog is cleared
   */
  public function testClearWatchdogTable(): void {
    $database = Database::getConnection();
    if ($database->schema()->tableExists('watchdog')) {
      $database->truncate('watchdog')->execute();
    }
  }

  /**
   * Hook into node creation to test `@beforeNodeCreate`.
   *
   * @beforeNodeCreate
   */
  public static function testAlterNodeParameters(BeforeNodeCreateScope $scope): void {
    parent::alterNodeParameters($scope);
    // @see `tests/behat/features/api.feature`
    // Change 'published on' to the expected 'created'.
    $node = $scope->getEntity();
    if (isset($node->{"published on"})) {
      $node->created = $node->{"published on"};
      unset($node->{"published on"});
    }
  }

  /**
   * Hook into term creation to test `@beforeTermCreate`.
   *
   * @beforeTermCreate
   */
  public static function testAlterTermParameters(EntityScope $scope): void {
    // @see `tests/behat/features/api.feature`
    // Change 'Label' to expected 'name'.
    $term = $scope->getEntity();
    if (isset($term->{'Label'})) {
      $term->name = $term->{'Label'};
      unset($term->{'Label'});
    }
  }

  /**
   * Hook into user creation to test `@beforeUserCreate`.
   *
   * @beforeUserCreate
   */
  public static function testAlterUserParameters(EntityScope $scope): void {
    // @see `tests/behat/features/api.feature`
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
  public static function testAfterNodeCreate(EntityScope $scope): void {
    if (!$node = $scope->getEntity()) {
      throw new \Exception('Failed to find a node in @afterNodeCreate hook.');
    }
  }

  /**
   * Test that a term is returned after term create.
   *
   * @afterTermCreate
   */
  public static function testAfterTermCreate(EntityScope $scope): void {
    if (!$term = $scope->getEntity()) {
      throw new \Exception('Failed to find a term in @afterTermCreate hook.');
    }
  }

  /**
   * Test that a user is returned after user create.
   *
   * @afterUserCreate
   */
  public static function testAfterUserCreate(EntityScope $scope): void {
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
  public function testTransformUsersTable(TableNode $user_table): TableNode {
    $aliases = [
      'country' => 'field_post_address:country',
      'city' => 'field_post_address:locality',
      'street' => 'field_post_address:thoroughfare',
      'postcode' => 'field_post_address:postal_code',
    ];

    // The first row of the table contains the field names.
    $table = $user_table->getTable();
    $firstRow = array_key_first($table);

    // Replace the aliased field names with the actual ones.
    foreach ($table[$firstRow] as $key => $alias) {
      if (array_key_exists($alias, $aliases)) {
        $table[$firstRow][$key] = $aliases[$alias];
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
   * @param \Behat\Gherkin\Node\TableNode $post_table
   *   The original table.
   *
   * @return \Behat\Gherkin\Node\TableNode
   *   The transformed table.
   *
   * @Transform rowtable:title,body,reference,date,links,select,address
   */
  public function testTransformPostContentTable(TableNode $post_table): TableNode {
    $aliases = [
      'reference' => 'field_post_reference',
      'date' => 'field_post_date',
      'links' => 'field_post_links',
      'select' => 'field_post_select',
      'address' => 'field_post_address',
    ];

    $table = $post_table->getTable();
    array_walk($table, function (array &$row) use ($aliases): void {
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
   * In Drupal it is possible to register a user without an e-mail address,
   * using only a username and password.
   *
   * This step definition is intended to test if users that are registered in
   * one context (in this case FeatureContext) can be accessed in other
   * contexts.
   *
   * See the scenario 'Logging in as a user without an e-mail address' in
   * d10.feature.
   *
   * @Given I am logged in as a user with name :name and password :password
   */
  public function testAssertAuthenticatedByUsernameAndPassword($name, $password): void {
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
   */
  public function testAssertBackendLogin(): void {
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
  public function testAssertBackendLoggedOut(): void {
    if ($this->getUserManager()->getCurrentUser()) {
      throw new \LogicException('User is still logged in in the manager.');
    }

    if (!\Drupal::currentUser()->isAnonymous()) {
      throw new \LogicException('User is still logged in on the backend.');
    }

    // Visit login page and ensure login form is present.
    $this->getSession()->visit($this->locatePath($this->getDrupalText('login_url')));
    $element = $this->getSession()->getPage();
    $element->fillField($this->getDrupalText('username_field'), 'foo');
  }

  /**
   * Logs out via the logout url rather than fast logout.
   *
   * @When I log out via the logout url
   */
  public function testLogoutViaUrl(): void {
    $this->logout(FALSE);
  }

  /**
   * Makes REQUEST_TIME stale by setting it to a past value.
   *
   * This simulates what happens during a long-running Behat test suite where
   * REQUEST_TIME becomes increasingly outdated.
   *
   * @Given the request time is :seconds seconds in the past
   *
   * @see https://github.com/jhedstrom/drupalextension/issues/179
   */
  public function testSetStaleRequestTime(int $seconds): void {
    $staleTime = time() - $seconds;
    $_SERVER['REQUEST_TIME'] = $staleTime;
    \Drupal::request()->server->set('REQUEST_TIME', $staleTime);
  }

  /**
   * Asserts that cron ran with a fresh request time.
   *
   * Reads the time drift recorded by behat_test_cron() and asserts it is
   * within the given threshold.
   *
   * @Then the cron request time drift should be less than :seconds seconds
   *
   * @see https://github.com/jhedstrom/drupalextension/issues/179
   */
  public function testAssertCronRequestTimeDrift(int $seconds): void {
    $drift = \Drupal::state()->get('behat_test.cron_time_drift');
    if ($drift === NULL) {
      throw new \RuntimeException('Cron time drift was not recorded. Ensure the behat_test module is enabled.');
    }
    if (abs($drift) >= $seconds) {
      $requestTime = \Drupal::state()->get('behat_test.cron_request_time');
      $actualTime = \Drupal::state()->get('behat_test.cron_actual_time');
      throw new ExpectationException(
        sprintf('Cron request time drift is %d seconds (request_time=%d, actual_time=%d), expected less than %d.', $drift, $requestTime, $actualTime, $seconds),
        $this->getSession()->getDriver()
      );
    }
  }

  /**
   * Performs a passing assertion step for testing.
   *
   * @When I use a test passing assertion step
   */
  public function testPassingAssertionStep(): void {
    // Noop.
  }

  /**
   * Performs a failing assertion step for testing.
   *
   * @When I use a test failing assertion step
   */
  public function testFailingAssertionStep(): void {
    throw new ExpectationException('This is a test failing assertion.', $this->getSession()->getDriver());
  }

  /**
   * Checks if the current scenario or feature has the given tag.
   *
   * @param string $tag
   *   The tag to check.
   *
   * @Then the :tag tag should be present
   */
  public function testAssertTagPresent($tag): void {
    if (!$this->hasTag($tag)) {
      throw new \Exception(sprintf('Expected tag %s was not found in the scenario or feature.', $tag));
    }
  }

  /**
   * Checks if the current scenario or feature does not have the given tag.
   *
   * @param string $tag
   *   The tag to check.
   *
   * @Then the :tag tag should not be present
   */
  public function testAssertTagNotPresent($tag): void {
    if ($this->hasTag($tag)) {
      throw new \Exception(sprintf('Expected tag %s was found in the scenario or feature.', $tag));
    }
  }

  /**
   * Throws a test assertion exception.
   *
   * @Given I throw a test assertion exception :calss with message :message
   */
  public function throwTestErrorException(string $name, string $message): void {
    if (!class_exists($name)) {
      throw new \RuntimeException(sprintf("Assertion exception class '%s' does not exist.", $name));
    }

    throw new $name($message);
  }

  /**
   * Throws a test runtime exception.
   *
   * @Given I throw a test runtime exception with message :message
   */
  public function throwTestRuntimeException(string $message): never {
    throw new \RuntimeException($message);
  }

}
