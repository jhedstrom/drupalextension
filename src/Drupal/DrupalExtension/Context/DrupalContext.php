<?php

declare(strict_types=1);

namespace Drupal\DrupalExtension\Context;

use Behat\Step\Given;
use Behat\Step\Then;
use Behat\Step\When;
use Behat\Behat\Context\TranslatableContext;
use Behat\Mink\Element\Element;
use Behat\Mink\Exception\ElementNotFoundException;
use Behat\Mink\Exception\ExpectationException;

use Behat\Gherkin\Node\TableNode;
use Drupal\Driver\Capability\CacheCapabilityInterface;
use Drupal\Driver\Capability\CronCapabilityInterface;
use Drupal\Driver\Capability\RoleCapabilityInterface;
use Drupal\Driver\Capability\UserCapabilityInterface;
use Drupal\Driver\Entity\EntityStub;
use Drupal\Driver\Entity\EntityStubInterface;

/**
 * Provides pre-built step definitions for interacting with Drupal.
 */
class DrupalContext extends RawDrupalContext implements TranslatableContext {

  /**
   * Returns list of definition translation resources paths.
   *
   * @return array<int, string>
   *   List of translation resource paths.
   */
  public static function getTranslationResources(): array {
    return self::getDrupalTranslationResources();
  }

  /**
   * Assert the user is anonymous.
   *
   * @code
   * Given I am an anonymous user
   * @endcode
   */
  #[Given('I am an anonymous user')]
  public function iAmAnonymous(): void {
    $this->logout(TRUE);
  }

  /**
   * Assert the user is not logged in.
   *
   * @code
   * Given I am not logged in
   * @endcode
   */
  #[Given('I am not logged in')]
  public function iAmNotLoggedIn(): void {
    $this->logout(TRUE);
  }

  /**
   * Log out the current user.
   *
   * @code
   * When I log out
   * @endcode
   */
  #[When('I log out')]
  public function iLogOut(): void {
    $this->logout(TRUE);
  }

  /**
   * Creates and authenticates a user with the given role(s).
   *
   * @code
   * Given I am logged in as a user with the "editor" role
   * Given I am logged in as a user with the "editor, admin" roles
   * @endcode
   */
  #[Given('I am logged in as a user with the :role role(s)')]
  public function assertAuthenticatedByRole(string $role): void {
    $this->createAndLoginUserWithRole($role);
  }

  /**
   * Creates and authenticates a user with the given single role.
   *
   * @code
   * Given I am logged in as an "editor"
   * @endcode
   */
  #[Given('I am logged in as a/an :role')]
  public function assertAuthenticatedByRoleShort(string $role): void {
    $this->createAndLoginUserWithRole($role);
  }

  /**
   * Creates and authenticates a user with the given role(s) and given fields.
   *
   * @code
   *   Given I am logged in as a user with the "editor" role and I have the following fields:
   *     | field_user_name    | John  |
   *     | field_user_surname | Smith |
   * @endcode
   */
  #[Given('I am logged in as a user with the :role role(s) and I have the following fields:')]
  public function assertAuthenticatedByRoleWithGivenFields(string $role, TableNode $fields): void {
    $this->createAndLoginUserWithRole($role, $fields->getRowsHash());
  }

  /**
   * Creates a user with the given role(s), optional extra fields, and logs in.
   *
   * @param string $role
   *   A single role, or multiple comma-separated roles.
   * @param array<string, mixed> $extra_fields
   *   Optional associative array of additional fields to set on the user.
   */
  protected function createAndLoginUserWithRole(string $role, array $extra_fields = []): void {
    $user = $this->createUserStub($role, $extra_fields);
    $this->userCreate($user);

    $driver = $this->getDriver();

    if (!$driver instanceof UserCapabilityInterface) {
      throw new \RuntimeException(sprintf('The active Drupal driver "%s" does not support user role assignment.', $driver::class));
    }

    $roles = explode(',', $role);
    $roles = array_map(trim(...), $roles);

    foreach ($roles as $role) {
      if (!in_array(strtolower($role), ['authenticated', 'authenticated user'], TRUE)) {
        $driver->userAddRole($user, $role);
      }
    }

    $this->login($user);
  }

  /**
   * Creates a user stub with random name, password, and email.
   *
   * @param string $role
   *   The role to assign.
   * @param array<string, mixed> $extra_fields
   *   Optional additional fields.
   */
  protected function createUserStub(string $role, array $extra_fields = []): EntityStubInterface {
    $name = $this->getRandom()->name(8);
    $stub = new EntityStub('user', NULL, [
      'name' => $name,
      'pass' => $this->getRandom()->name(16),
      'role' => $role,
      'mail' => $name . '@example.com',
    ]);

    foreach ($extra_fields as $field => $value) {
      $stub->setValue($field, $value);
    }

    return $stub;
  }

  /**
   * Log in as an existing user by name.
   *
   * @code
   * Given I am logged in as "admin"
   * @endcode
   */
  #[Given('I am logged in as :name')]
  public function assertLoggedInByName(string $name): void {
    $this->login($this->getUserManager()->getUser($name));
  }

  /**
   * Log in as a user with specific permissions.
   *
   * @code
   * Given I am logged in as a user with the "administer nodes" permission
   * Given I am logged in as a user with the "administer nodes, bypass node access" permissions
   * @endcode
   */
  #[Given('I am logged in as a user with the :permissions permission(s)')]
  public function assertLoggedInWithPermissions(string $permissions): void {
    $driver = $this->getDriver();

    if (!$driver instanceof RoleCapabilityInterface || !$driver instanceof UserCapabilityInterface) {
      throw new \RuntimeException(sprintf('The active Drupal driver "%s" does not support role and user management.', $driver::class));
    }

    $permissions = array_map(trim(...), explode(',', $permissions));
    $role = $driver->roleCreate($permissions);

    $user = $this->createUserStub($role);
    $this->userCreate($user);
    $driver->userAddRole($user, $role);
    $this->roles[] = $role;

    $this->login($user);
  }

  /**
   * Retrieve a table row containing specified text from a given element.
   *
   * @param \Behat\Mink\Element\Element $element
   *   The element to search within, such as a table or the page.
   * @param string $search
   *   The text to search for in the table row.
   *
   * @return \Behat\Mink\Element\NodeElement
   *   The table row element containing the search text.
   *
   * @throws \Exception
   */
  public function getTableRow(Element $element, string $search) {
    $rows = $element->findAll('css', 'tr');
    if (empty($rows)) {
      throw new ElementNotFoundException($this->getSession()->getDriver(), 'row', 'css', 'tr');
    }
    foreach ($rows as $row) {
      if (str_contains($row->getText(), $search)) {
        return $row;
      }
    }
    throw new ElementNotFoundException($this->getSession()->getDriver(), 'row', 'text', $search);
  }

  /**
   * Find text in a table row containing given text.
   *
   * @code
   * Then I should see the text :text in the :rowText row
   * @endcode
   */
  #[Then('I should see the text :text in the :rowText row')]
  public function tableRowTextAssertIsVisible(string $text, string $rowText): void {
    $row = $this->getTableRow($this->getSession()->getPage(), $rowText);
    if (!str_contains($row->getText(), $text)) {
      throw new ExpectationException(sprintf('Found a row containing "%s", but it did not contain the text "%s".', $rowText, $text), $this->getSession()->getDriver());
    }
  }

  /**
   * Asset text not in a table row containing given text.
   *
   * @code
   * Then I should not see the text :text in the :rowText row
   * @endcode
   */
  #[Then('I should not see the text :text in the :rowText row')]
  public function tableRowTextAssertIsNotVisible(string $text, string $rowText): void {
    $row = $this->getTableRow($this->getSession()->getPage(), $rowText);
    if (str_contains($row->getText(), $text)) {
      throw new ExpectationException(sprintf('Found a row containing "%s", but it contained the text "%s".', $rowText, $text), $this->getSession()->getDriver());
    }
  }

  /**
   * Asserts a link exists in a table row containing given text.
   *
   * This is for administrative pages such as the administer content types
   * screen found at `admin/structure/types`.
   *
   * @code
   * Then I should see the :link in the :rowText row
   * @endcode
   */
  #[Then('I should see the :link in the :rowText row')]
  public function tableRowLinkAssertExists(string $link, string $rowText): void {
    $page = $this->getSession()->getPage();
    if ($this->getTableRow($page, $rowText)->findLink($link)) {
      return;
    }
    throw new ElementNotFoundException($this->getSession()->getDriver(), sprintf('link in the "%s" row', $rowText), 'id|title|alt|text', $link);
  }

  /**
   * Asserts a link does not exist in a table row containing given text.
   *
   * This is for administrative pages such as the administer content types
   * screen found at `admin/structure/types`.
   *
   * @code
   * Then I should not see the :link in the :rowText row
   * @endcode
   */
  #[Then('I should not see the :link in the :rowText row')]
  public function tableRowLinkAssertNotExists(string $link, string $rowText): void {
    $page = $this->getSession()->getPage();
    if ($this->getTableRow($page, $rowText)->findLink($link)) {
      throw new ExpectationException(sprintf('Found a row containing "%s" with a "%s" link on the page %s', $rowText, $link, $this->getSession()->getCurrentUrl()), $this->getSession()->getDriver());
    }
  }

  /**
   * Clicks a link in a table row containing given text.
   *
   * This is for administrative pages such as the administer content types
   * screen found at `admin/structure/types`.
   *
   * @code
   * Given I click "Edit" in the "My article" row
   * @endcode
   */
  #[Given('I click :link in the :rowText row')]
  public function assertClickInTableRow(string $link, string $rowText): void {
    $page = $this->getSession()->getPage();
    if ($link_element = $this->getTableRow($page, $rowText)->findLink($link)) {
      // Click the link and return.
      $link_element->click();
      return;
    }
    throw new ElementNotFoundException($this->getSession()->getDriver(), sprintf('link in the "%s" row', $rowText), 'id|title|alt|text', $link);
  }

  /**
   * Attempts to find a button in a table row containing giving text.
   *
   * This is for entity reference forms like Paragraphs, Inline entity form,
   * etc. where there may be multiple entities in a table, each with separate
   * edit buttons.
   *
   * @code
   * Given I press "Remove" in the "My article" row
   * @endcode
   */
  #[Given('I press :button in the :rowText row')]
  public function assertPressInTableRow(string $button, string $rowText): void {
    $page = $this->getSession()->getPage();
    if ($button_element = $this->getTableRow($page, $rowText)->findButton($button)) {
      // Press the button and return.
      $button_element->press();
      return;
    }
    throw new ElementNotFoundException($this->getSession()->getDriver(), sprintf('button in the "%s" row', $rowText), 'id|name|title|alt|value', $button);
  }

  /**
   * Clear the Drupal cache.
   *
   * @code
   * Given the cache has been cleared
   * @endcode
   */
  #[Given('the cache has been cleared')]
  public function assertCacheClear(): void {
    $driver = $this->getDriver();

    if (!$driver instanceof CacheCapabilityInterface) {
      throw new \RuntimeException(sprintf('The active Drupal driver "%s" does not support cache clearing.', $driver::class));
    }

    $driver->cacheClear();
  }

  /**
   * Run Drupal cron.
   *
   * @code
   * Given I run cron
   * @endcode
   */
  #[Given('I run cron')]
  public function assertCron(): void {
    $driver = $this->getDriver();

    if (!$driver instanceof CronCapabilityInterface) {
      throw new \RuntimeException(sprintf('The active Drupal driver "%s" does not support running cron.', $driver::class));
    }

    $driver->cronRun();
  }

  /**
   * View content of the given type with the given title.
   *
   * @code
   * Given I am viewing an "article" with the title "Test article"
   * @endcode
   */
  #[Given('I am viewing a/an :type with the title :title')]
  public function iAmViewingNode(string $type, string $title): void {
    $this->createAndVisitNode($type, $title);
  }

  /**
   * View content of the given type (with the explicit "content" word).
   *
   * @code
   * Given I am viewing an "article" content with the title "Test article"
   * @endcode
   */
  #[Given('I am viewing a/an :type content with the title :title')]
  public function iAmViewingNodeContent(string $type, string $title): void {
    $this->createAndVisitNode($type, $title);
  }

  /**
   * Create content of the given type and visit it.
   *
   * @code
   * Given a "page" with the title "About us"
   * @endcode
   */
  #[Given('a/an :type with the title :title')]
  public function aNode(string $type, string $title): void {
    $this->createAndVisitNode($type, $title);
  }

  /**
   * Create content (with the explicit "content" word) and visit it.
   *
   * @code
   * Given a "page" content with the title "About us"
   * @endcode
   */
  #[Given('a/an :type content with the title :title')]
  public function aNodeContent(string $type, string $title): void {
    $this->createAndVisitNode($type, $title);
  }

  /**
   * Creates content authored by the current user.
   *
   * @code
   * Given I am viewing my "article" with the title "My article"
   * @endcode
   */
  #[Given('I am viewing my :type with the title :title')]
  public function createMyNode(string $type, string $title): void {
    $this->createAndVisitMyNode($type, $title);
  }

  /**
   * Creates content authored by the current user (with the "content" word).
   *
   * @code
   * Given I am viewing my "article" content with the title "My article"
   * @endcode
   */
  #[Given('I am viewing my :type content with the title :title')]
  public function createMyNodeContent(string $type, string $title): void {
    $this->createAndVisitMyNode($type, $title);
  }

  /**
   * Creates content of a given type.
   *
   * @code
   *   Given the following "article" content:
   *     | title      | status |
   *     | My article | 1      |
   * @endcode
   */
  #[Given('the following :type content:')]
  public function createNodes(string $type, TableNode $nodesTable): void {
    foreach ($nodesTable->getHash() as $node_hash) {
      $stub = new EntityStub('node', $type, $node_hash);
      $this->nodeCreate($stub);
    }
  }

  /**
   * Creates content of the given type and visits it.
   *
   * @code
   *   Given I am viewing an "article" with the following fields:
   *     | title | My article     |
   *     | body  | Lorem ipsum    |
   * @endcode
   */
  #[Given('I am viewing a/an :type with the following fields:')]
  public function assertViewingNode(string $type, TableNode $fields): void {
    $this->createAndVisitNodeFromTable($type, $fields);
  }

  /**
   * Creates content (with explicit "content" word) and visits it.
   *
   * @code
   *   Given I am viewing an "article" content with the following fields:
   *     | title | My article     |
   *     | body  | Lorem ipsum    |
   * @endcode
   */
  #[Given('I am viewing a/an :type content with the following fields:')]
  public function assertViewingNodeContent(string $type, TableNode $fields): void {
    $this->createAndVisitNodeFromTable($type, $fields);
  }

  /**
   * Asserts that a given content type is editable.
   *
   * @code
   * Then I should be able to edit the :type
   * @endcode
   */
  #[Then('I should be able to edit the :type')]
  public function nodeTypeAssertIsEditable(string $type): void {
    $this->assertNodeTypeIsEditable($type);
  }

  /**
   * Asserts that a given content type (with explicit "content") is editable.
   *
   * @code
   * Then I should be able to edit the :type content
   * @endcode
   */
  #[Then('I should be able to edit the :type content')]
  public function nodeTypeContentAssertIsEditable(string $type): void {
    $this->assertNodeTypeIsEditable($type);
  }

  /**
   * Creates a term on an existing vocabulary and visits it.
   *
   * @code
   * Given I am viewing a "tags" term with the name "Sports"
   * @endcode
   */
  #[Given('I am viewing a/an :vocabulary term with the name :name')]
  public function iAmViewingTerm(string $vocabulary, string $name): void {
    $this->createAndVisitTerm($vocabulary, $name);
  }

  /**
   * Creates a term on an existing vocabulary without explicit visit phrasing.
   *
   * @code
   * Given an "categories" term with the name "News"
   * @endcode
   */
  #[Given('a/an :vocabulary term with the name :name')]
  public function aTerm(string $vocabulary, string $name): void {
    $this->createAndVisitTerm($vocabulary, $name);
  }

  /**
   * Creates multiple users.
   *
   * @code
   *   Given the following users:
   *     | name     | mail            | roles  |
   *     | Joe User | joe@example.com | editor |
   * @endcode
   */
  #[Given('the following users:')]
  public function createUsers(TableNode $usersTable): void {
    $driver = $this->getDriver();

    if (!$driver instanceof UserCapabilityInterface) {
      throw new \RuntimeException(sprintf('The active Drupal driver "%s" does not support user role assignment.', $driver::class));
    }

    foreach ($usersTable->getHash() as $user_hash) {
      // Split out roles to process after user is created.
      $roles = [];

      if (isset($user_hash['roles'])) {
        $roles = explode(',', $user_hash['roles']);
        $roles = array_filter(array_map(trim(...), $roles));
        unset($user_hash['roles']);
      }

      // Set a password if none was supplied.
      if (!isset($user_hash['pass'])) {
        $user_hash['pass'] = $this->getRandom()->name();
      }

      $stub = new EntityStub('user', NULL, $user_hash);
      $this->userCreate($stub);

      foreach ($roles as $role) {
        $driver->userAddRole($stub, $role);
      }
    }
  }

  /**
   * Creates one or more terms on an existing vocabulary.
   *
   * @code
   *   Given the following "tags" terms:
   *     | name   |
   *     | Sports |
   *     | News   |
   * @endcode
   */
  #[Given('the following :vocabulary terms:')]
  public function createTerms(string $vocabulary, TableNode $termsTable): void {
    $machine_name = $this->resolveVocabularyMachineName($vocabulary);

    foreach ($termsTable->getHash() as $terms_hash) {
      $terms_hash['vocabulary_machine_name'] = $machine_name;
      $stub = new EntityStub('taxonomy_term', $machine_name, $terms_hash);
      $this->termCreate($stub);
    }
  }

  /**
   * Creates one or more languages.
   *
   * @param \Behat\Gherkin\Node\TableNode $langcodesTable
   *   The table listing languages by their ISO code.
   *
   * @code
   *   Given the/these (following )languages are available:
   *     | languages |
   *     | en        |
   *     | fr        |
   * @endcode
   */
  #[Given('the/these (following )languages are available:')]
  public function createLanguages(TableNode $langcodesTable): void {
    foreach ($langcodesTable->getHash() as $row) {
      $stub = new EntityStub('language', NULL, [
        'langcode' => $row['languages'],
      ]);
      $this->languageCreate($stub);
    }
  }

  /**
   * Pauses the scenario until the user presses a key.
   *
   * Useful when debugging a scenario.
   *
   * @code
   * When I break
   * @endcode
   */
  #[When('(I )break')]
  // phpcs:ignore Drupal.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
  public function iPutABreakpoint(): void {
    fwrite(STDOUT, "\033[s \033[93m[Breakpoint] Press \033[1;93m[RETURN]\033[0;93m to continue, or 'q' to quit...\033[0m");
    do {
      $line = trim((string) fgets(STDIN, 1024));
      // Note: this assumes ASCII encoding.  Should probably be revamped to
      // handle other character sets.
      $char_code = ord($line);
      switch ($char_code) {
        // CR.
        case 0:
          // Y.
        case 121:
          // Y.
        case 89:
          break 2;

        // Case 78: //N
        // case 110: //n.
        // q.
        case 113:
          // Q.
        case 81:
          throw new \RuntimeException("Exiting test intentionally.");

        default:
          fwrite(STDOUT, sprintf("\nInvalid entry '%s'.  Please enter 'y', 'q', or the enter key.\n", $line));
          break;
      }
    } while (TRUE);
    fwrite(STDOUT, "\033[u");
  }

  /**
   * Create a node and visit its detail page.
   */
  protected function createAndVisitNode(string $type, string $title): void {
    $stub = new EntityStub('node', $type, ['title' => $title]);
    $this->nodeCreate($stub);
    $this->getSession()->visit($this->locatePath('/node/' . $stub->getId()));
  }

  /**
   * Create a node owned by the current user and visit it.
   */
  protected function createAndVisitMyNode(string $type, string $title): void {
    if ($this->getUserManager()->currentUserIsAnonymous()) {
      throw new \RuntimeException('There is no current logged in user to create a node for.');
    }

    $current_user = $this->getUserManager()->getCurrentUser();

    if (!$current_user instanceof EntityStubInterface) {
      throw new \RuntimeException('There is no current logged in user to create a node for.');
    }

    $stub = new EntityStub('node', $type, [
      'title' => $title,
      'body' => $this->getRandom()->name(255),
      'uid' => $current_user->getValue('uid') ?? $current_user->getId(),
    ]);
    $this->nodeCreate($stub);

    $this->getSession()->visit($this->locatePath('/node/' . $stub->getId()));
  }

  /**
   * Create a node from a TableNode of fields and visit it.
   */
  protected function createAndVisitNodeFromTable(string $type, TableNode $fields): void {
    $stub = new EntityStub('node', $type, $fields->getRowsHash());
    $this->nodeCreate($stub);

    $this->getSession()->visit($this->locatePath('/node/' . $stub->getId()));
  }

  /**
   * Create a term on an existing vocabulary and visit it.
   */
  protected function createAndVisitTerm(string $vocabulary, string $name): void {
    $machine_name = $this->resolveVocabularyMachineName($vocabulary);
    $stub = new EntityStub('taxonomy_term', $machine_name, [
      'name' => $name,
      'vocabulary_machine_name' => $machine_name,
      'description' => $this->getRandom()->name(255),
    ]);
    $this->termCreate($stub);

    $this->getSession()->visit($this->locatePath('/taxonomy/term/' . $stub->getId()));
  }

  /**
   * Create a node of the given type and verify its edit page returns 200.
   */
  protected function assertNodeTypeIsEditable(string $type): void {
    $stub = new EntityStub('node', $type, ['title' => 'Test ' . $type]);
    $this->nodeCreate($stub);

    $this->getSession()->visit($this->locatePath('/node/' . $stub->getId() . '/edit'));

    $this->assertSession()->statusCodeEquals(200);
  }

}
