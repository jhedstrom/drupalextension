<?php

declare(strict_types=1);

use Behat\Gherkin\Node\TableNode;
use Drupal\DrupalExtension\Context\RawDrupalContext;
use Drupal\DrupalExtension\Hook\Scope\BeforeNodeCreateScope;
use Drupal\DrupalExtension\Hook\Scope\EntityScope;
use Drupal\DrupalExtension\TagTrait;

/**
 * Features context for testing the Drupal Extension.
 *
 * @phpcs:disable PSR1.Classes.ClassDeclaration.MissingNamespace
 */
class FeatureContext extends RawDrupalContext
{

    use TagTrait;

    /**
     * Hook into node creation to test `@beforeNodeCreate`
     *
     * @beforeNodeCreate
     */
    public static function alterNodeParameters(BeforeNodeCreateScope $scope): void
    {
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
     * Hook into term creation to test `@beforeTermCreate`
     *
     * @beforeTermCreate
     */
    public static function alterTermParameters(EntityScope $scope): void
    {
        // @see `tests/behat/features/api.feature`
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
    public static function alterUserParameters(EntityScope $scope): void
    {
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
    public static function afterNodeCreate(EntityScope $scope): void
    {
        if (!$node = $scope->getEntity()) {
            throw new \Exception('Failed to find a node in @afterNodeCreate hook.');
        }
    }

    /**
     * Test that a term is returned after term create.
     *
     * @afterTermCreate
     */
    public static function afterTermCreate(EntityScope $scope): void
    {
        if (!$term = $scope->getEntity()) {
            throw new \Exception('Failed to find a term in @afterTermCreate hook.');
        }
    }

    /**
     * Test that a user is returned after user create.
     *
     * @afterUserCreate
     */
    public static function afterUserCreate(EntityScope $scope): void
    {
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
    public function castUsersTable(TableNode $user_table): TableNode
    {
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
     * @param TableNode $post_table
     *   The original table.
     *
     * @return TableNode
     *   The transformed table.
     *
     * @Transform rowtable:title,body,reference,date,links,select,address
     */
    public function transformPostContentTable(TableNode $post_table): TableNode
    {
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
    public function assertAuthenticatedByUsernameAndPassword($name, $password): void
    {
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
    public function assertBackendLogin(): void
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
    public function assertBackendLoggedOut(): void
    {
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
     * @Then I log out via the logout url
     */
    public function logoutViaUrl(): void
    {
        $this->logout(false);
    }

    /**
     * Checks if the current scenario or feature has the given tag.
     *
     * @Then the :tag tag should be present
     *
     * @param string $tag
     *   The tag to check.
     */
    public function shouldHaveTag($tag): void
    {
        if (!$this->hasTag($tag)) {
            throw new \Exception(sprintf('Expected tag %s was not found in the scenario or feature.', $tag));
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
    public function shouldNotHaveTag($tag): void
    {
        if ($this->hasTag($tag)) {
            throw new \Exception(sprintf('Expected tag %s was found in the scenario or feature.', $tag));
        }
    }
}
