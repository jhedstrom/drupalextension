<?php

declare(strict_types=1);

namespace Drupal\DrupalExtension\Context;

use Behat\Step\Given;
use Behat\Step\Then;
use Behat\Behat\Context\TranslatableContext;
use Behat\Mink\Element\Element;

use Behat\Gherkin\Node\TableNode;

/**
 * Provides pre-built step definitions for interacting with Drupal.
 */
class DrupalContext extends RawDrupalContext implements TranslatableContext {

  /**
   * Returns list of definition translation resources paths.
   *
   * @return array
   *   List of translation resource paths.
   */
  public static function getTranslationResources() {
    return self::getDrupalTranslationResources();
  }

  /**
   * Assert the user is anonymous or log out.
   *
   * @code
   * Given I am an anonymous user
   * Given I am not logged in
   * Then I log out
   * @endcode
   */
  #[Given('I am an anonymous user')]
  #[Given('I am not logged in')]
  #[Then('I log out')]
  public function assertAnonymousUser(): void {
    // Verify the user is logged out.
    $this->logout(TRUE);
  }

  /**
   * Creates and authenticates a user with the given role(s).
   *
   * @code
   * Given I am logged in as a user with the "editor" role
   * Given I am logged in as a user with the "editor, admin" roles
   * Given I am logged in as an "editor"
   * @endcode
   */
  #[Given('I am logged in as a user with the :role role(s)')]
  #[Given('I am logged in as a/an :role')]
  public function assertAuthenticatedByRole(string $role): void {
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
   * @param array $extra_fields
   *   Optional associative array of additional fields to set on the user.
   */
  protected function createAndLoginUserWithRole(string $role, array $extra_fields = []): void {
    $user = $this->createUserStub($role, $extra_fields);
    $this->userCreate($user);

    $roles = explode(',', $role);

    $roles = array_map(trim(...), $roles);
    foreach ($roles as $role) {
      if (!in_array(strtolower($role), ['authenticated', 'authenticated user'], TRUE)) {
        $this->getDriver()->userAddRole($user, $role);
      }
    }

    $this->login($user);
  }

  /**
   * Creates a user stub with random name, password, and email.
   *
   * @param string $role
   *   The role to assign.
   * @param array $extra_fields
   *   Optional additional fields.
   */
  protected function createUserStub(string $role, array $extra_fields = []): \stdClass {
    $user = (object) [
      'name' => $this->getRandom()->name(8),
      'pass' => $this->getRandom()->name(16),
      'role' => $role,
    ];

    $user->mail = $user->name . '@example.com';

    foreach ($extra_fields as $field => $value) {
      $user->{$field} = $value;
    }

    return $user;
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
    $permissions = array_map(trim(...), explode(',', $permissions));
    $role = $this->getDriver()->roleCreate($permissions);

    $user = $this->createUserStub($role);
    $this->userCreate($user);
    $this->getDriver()->userAddRole($user, $role);
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
      throw new \Exception(sprintf('No rows found on the page %s', $this->getSession()->getCurrentUrl()));
    }
    foreach ($rows as $row) {
      if (str_contains($row->getText(), $search)) {
        return $row;
      }
    }
    throw new \Exception(sprintf('Failed to find a row containing "%s" on the page %s', $search, $this->getSession()->getCurrentUrl()));
  }

  /**
   * Find text in a table row containing given text.
   *
   * @code
   * Then I should see "Edit" in the "My article" row
   * Then I should see the text "Edit" in the "My article" row
   * @endcode
   */
  #[Then('I should see (the text ):text in the :rowText row')]
  public function assertTextInTableRow(string $text, string $rowText): void {
    $row = $this->getTableRow($this->getSession()->getPage(), $rowText);
    if (!str_contains($row->getText(), $text)) {
      throw new \Exception(sprintf('Found a row containing "%s", but it did not contain the text "%s".', $rowText, $text));
    }
  }

  /**
   * Asset text not in a table row containing given text.
   *
   * @code
   * Then I should not see "Delete" in the "My article" row
   * Then I should not see the text "Delete" in the "My article" row
   * @endcode
   */
  #[Then('I should not see (the text ):text in the :rowText row')]
  public function assertTextNotInTableRow(string $text, string $rowText): void {
    $row = $this->getTableRow($this->getSession()->getPage(), $rowText);
    if (str_contains($row->getText(), $text)) {
      throw new \Exception(sprintf('Found a row containing "%s", but it contained the text "%s".', $rowText, $text));
    }
  }

  /**
   * Asserts a link exists in a table row containing given text.
   *
   * This is for administrative pages such as the administer content types
   * screen found at `admin/structure/types`.
   *
   * @code
   * Then I see the "Edit" in the "My article" row
   * Then I should see the "Edit" in the "My article" row
   * @endcode
   */
  #[Then('I (should )see the :link in the :rowText row')]
  public function assertLinkInTableRow(string $link, string $rowText): void {
    $page = $this->getSession()->getPage();
    if ($this->getTableRow($page, $rowText)->findLink($link)) {
      return;
    }
    throw new \Exception(sprintf('Found a row containing "%s", but no "%s" link on the page %s', $rowText, $link, $this->getSession()->getCurrentUrl()));
  }

  /**
   * Asserts a link does not exist in a table row containing given text.
   *
   * This is for administrative pages such as the administer content types
   * screen found at `admin/structure/types`.
   *
   * @code
   * Then I should not see the "Delete" in the "My article" row
   * @endcode
   */
  #[Then('I should not see the :link in the :rowText row')]
  public function assertNotLinkInTableRow(string $link, string $rowText): void {
    $page = $this->getSession()->getPage();
    if ($this->getTableRow($page, $rowText)->findLink($link)) {
      throw new \Exception(sprintf('Found a row containing "%s" with a "%s" link on the page %s', $rowText, $link, $this->getSession()->getCurrentUrl()));
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
    if ($linkElement = $this->getTableRow($page, $rowText)->findLink($link)) {
      // Click the link and return.
      $linkElement->click();
      return;
    }
    throw new \Exception(sprintf('Found a row containing "%s", but no "%s" link on the page %s', $rowText, $link, $this->getSession()->getCurrentUrl()));
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
    if ($buttonElement = $this->getTableRow($page, $rowText)->findButton($button)) {
      // Press the button and return.
      $buttonElement->press();
      return;
    }
    throw new \Exception(sprintf('Found a row containing "%s", but no "%s" button on the page %s', $rowText, $button, $this->getSession()->getCurrentUrl()));
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
    $this->getDriver()->clearCache();
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
    $this->getDriver()->runCron();
  }

  /**
   * Creates content of the given type.
   *
   * @code
   * Given I am viewing an "article" with the title "Test article"
   * Given I am viewing an "article" content with the title "Test article"
   * Given a "page" with the title "About us"
   * @endcode
   */
  #[Given('I am viewing a/an :type (content )with the title :title')]
  #[Given('a/an :type (content )with the title :title')]
  public function createNode(string $type, string $title): void {
    // @todo make this easily extensible.
    $node = (object) [
      'title' => $title,
      'type' => $type,
    ];
    $saved = $this->nodeCreate($node);
    // Set internal page on the new node.
    $this->getSession()->visit($this->locatePath('/node/' . $saved->nid));
  }

  /**
   * Creates content authored by the current user.
   *
   * @code
   * Given I am viewing my "article" with the title "My article"
   * Given I am viewing my "article" content with the title "My article"
   * @endcode
   */
  #[Given('I am viewing my :type (content )with the title :title')]
  public function createMyNode(string $type, string $title): void {
    if ($this->getUserManager()->currentUserIsAnonymous()) {
      throw new \Exception('There is no current logged in user to create a node for.');
    }

    $node = (object) [
      'title' => $title,
      'type' => $type,
      'body' => $this->getRandom()->name(255),
      'uid' => $this->getUserManager()->getCurrentUser()->uid,
    ];
    $saved = $this->nodeCreate($node);

    // Set internal page on the new node.
    $this->getSession()->visit($this->locatePath('/node/' . $saved->nid));
  }

  /**
   * Creates content of a given type.
   *
   * @code
   *   Given "article" content:
   *     | title      | status |
   *     | My article | 1      |
   * @endcode
   */
  #[Given(':type content:')]
  public function createNodes(string $type, TableNode $nodesTable): void {
    foreach ($nodesTable->getHash() as $nodeHash) {
      $node = (object) $nodeHash;
      $node->type = $type;
      $this->nodeCreate($node);
    }
  }

  /**
   * Creates content of the given type and visits it.
   *
   * @code
   *   Given I am viewing an "article":
   *     | title | My article     |
   *     | body  | Lorem ipsum    |
   *   Given I am viewing an "article" content:
   *     | title | My article     |
   *     | body  | Lorem ipsum    |
   * @endcode
   */
  #[Given('I am viewing a/an :type( content):')]
  public function assertViewingNode(string $type, TableNode $fields): void {
    $node = (object) [
      'type' => $type,
    ];
    foreach ($fields->getRowsHash() as $field => $value) {
      $node->{$field} = $value;
    }

    $saved = $this->nodeCreate($node);

    // Set internal browser on the node.
    $this->getSession()->visit($this->locatePath('/node/' . $saved->nid));
  }

  /**
   * Asserts that a given content type is editable.
   *
   * @code
   * Then I should be able to edit an "article"
   * Then I should be able to edit an "article" content
   * @endcode
   */
  #[Then('I should be able to edit a/an :type( content)')]
  public function assertEditNodeOfType(string $type): void {
    $node = (object) [
      'type' => $type,
      'title' => 'Test ' . $type,
    ];
    $saved = $this->nodeCreate($node);

    // Set internal browser on the node edit page.
    $this->getSession()->visit($this->locatePath('/node/' . $saved->nid . '/edit'));

    // Test status.
    $this->assertSession()->statusCodeEquals(200);
  }

  /**
   * Creates a term on an existing vocabulary.
   *
   * @code
   * Given I am viewing a "tags" term with the name "Sports"
   * Given an "categories" term with the name "News"
   * @endcode
   */
  #[Given('I am viewing a/an :vocabulary term with the name :name')]
  #[Given('a/an :vocabulary term with the name :name')]
  public function createTerm(string $vocabulary, string $name): void {
    // @todo make this easily extensible.
    $term = (object) [
      'name' => $name,
      'vocabulary_machine_name' => $vocabulary,
      'description' => $this->getRandom()->name(255),
    ];
    $saved = $this->termCreate($term);

    // Set internal page on the term.
    $this->getSession()->visit($this->locatePath('/taxonomy/term/' . $saved->tid));
  }

  /**
   * Creates multiple users.
   *
   * @code
   *   Given users:
   *     | name     | mail            | roles  |
   *     | Joe User | joe@example.com | editor |
   * @endcode
   */
  #[Given('users:')]
  public function createUsers(TableNode $usersTable): void {
    foreach ($usersTable->getHash() as $userHash) {
      // Split out roles to process after user is created.
      $roles = [];
      if (isset($userHash['roles'])) {
        $roles = explode(',', $userHash['roles']);
        $roles = array_filter(array_map(trim(...), $roles));
        unset($userHash['roles']);
      }

      $user = (object) $userHash;
      // Set a password.
      if (!isset($user->pass)) {
        $user->pass = $this->getRandom()->name();
      }
      $this->userCreate($user);

      // Assign roles.
      foreach ($roles as $role) {
        $this->getDriver()->userAddRole($user, $role);
      }
    }
  }

  /**
   * Creates one or more terms on an existing vocabulary.
   *
   * @code
   *   Given "tags" terms:
   *     | name   |
   *     | Sports |
   *     | News   |
   * @endcode
   */
  #[Given(':vocabulary terms:')]
  public function createTerms(string $vocabulary, TableNode $termsTable): void {
    foreach ($termsTable->getHash() as $termsHash) {
      $term = (object) $termsHash;
      $term->vocabulary_machine_name = $vocabulary;
      $this->termCreate($term);
    }
  }

  /**
   * Creates one or more languages.
   *
   * @param \Behat\Gherkin\Node\TableNode $langcodesTable
   *   The table listing languages by their ISO code.
   *
   * @code
   *   Given the following languages are available:
   *     | languages |
   *     | en        |
   *     | fr        |
   *   Given these languages are available:
   *     | languages |
   *     | de        |
   * @endcode
   */
  #[Given('the/these (following )languages are available:')]
  public function createLanguages(TableNode $langcodesTable): void {
    foreach ($langcodesTable->getHash() as $row) {
      $language = (object) [
        'langcode' => $row['languages'],
      ];
      $this->languageCreate($language);
    }
  }

  /**
   * Pauses the scenario until the user presses a key. Useful when debugging a scenario.
   *
   * @code
   * Then break
   * Then I break
   * @endcode
   */
  #[Then('(I )break')]
  // phpcs:ignore Drupal.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
  public function iPutABreakpoint(): void {
    fwrite(STDOUT, "\033[s \033[93m[Breakpoint] Press \033[1;93m[RETURN]\033[0;93m to continue, or 'q' to quit...\033[0m");
    do {
      $line = trim(fgets(STDIN, 1024));
      // Note: this assumes ASCII encoding.  Should probably be revamped to
      // handle other character sets.
      $charCode = ord($line);
      switch ($charCode) {
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
          throw new \Exception("Exiting test intentionally.");

        default:
          fwrite(STDOUT, sprintf("\nInvalid entry '%s'.  Please enter 'y', 'q', or the enter key.\n", $line));
          break;
      }
    } while (TRUE);
    fwrite(STDOUT, "\033[u");
  }

}
