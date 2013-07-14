<?php

use Drupal\DrupalExtension\Context\DrupalContext,
    Drupal\DrupalExtension\Event\EntityEvent;

use Behat\Behat\Exception\PendingException;

use Behat\Gherkin\Node\PyStringNode,
    Behat\Gherkin\Node\TableNode;

require 'vendor/autoload.php';

/**
 * Features context for custom step-definitions.
 *
 * @todo we are duplicating code from Behat's FeatureContext here for the
 * purposes of testing since we can't easily run that as a subcontext due to
 * naming conflicts.
 */
class FeatureContext extends DrupalContext {
  /**
   * Initialize the needed step definitions for subcontext testing.
   */
  public function __construct() {
    $this->useContext('behat_feature_context', new BehatFeatureContext());
  }

  /**
   * Hook into node creation to test `@beforeNodeCreate`
   *
   * @beforeNodeCreate
   */
  public static function alterNodeParameters(EntityEvent $event) {
    // @see `features/api.feature`
    // Change 'published on' to the expected 'created'.
    $node = $event->getEntity();
    if (isset($node->{"published on"})) {
      $node->created = $node->{"published on"};
      unset($node->{"published on"});
    }
  }

  /**
   * Hook into user creation to test `@beforeUserCreate`
   *
   * @beforeUserCreate
   */
  public static function alterUserParameters(EntityEvent $event) {
    // @see `features/api.feature`
    // Concatenate 'First name' and 'Last name' to form user name.
    $user = $event->getEntity();
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
}
