<?php

namespace spec\Drupal\DrupalExtension\Manager;

use Drupal\Driver\Entity\EntityStub;
use Drupal\DrupalExtension\Manager\DrupalUserManager;
use PhpSpec\ObjectBehavior;

/**
 * Tests the DrupalUserManager class.
 */
class DrupalUserManagerSpec extends ObjectBehavior {

  public function it_is_initializable() {
    $this->shouldHaveType(DrupalUserManager::class);
  }

  public function it_can_set_and_get_the_current_user() {
    $user = new EntityStub('user', NULL, ['name' => 'some_name']);
    $this->setCurrentUser($user);
    $this->getCurrentUser()->shouldBe($user);
  }

  public function it_can_add_and_remove_users() {
    $user = new EntityStub('user', NULL, ['name' => 'some_name']);
    $this->addUser($user);
    $this->getUser('some_name')->shouldBe($user);
    $this->removeUser('some_name');
    $this->shouldThrow(\InvalidArgumentException::class)->duringGetUser('some_name');
  }

  public function it_can_get_all_registered_users() {
    $this->hasUsers()->shouldBe(FALSE);
    $user = new EntityStub('user', NULL, ['name' => 'some_name']);
    $this->addUser($user);
    $this->hasUsers()->shouldBe(TRUE);
    $this->getUsers()->shouldBe(['some_name' => $user]);
  }

  public function it_can_determine_anonymous_users() {
    $this->currentUserIsAnonymous()->shouldBe(TRUE);
    $user = new EntityStub('user', NULL, ['name' => 'some_name']);
    $this->setCurrentUser($user);
    $this->currentUserIsAnonymous()->shouldBe(FALSE);
  }

  public function it_can_check_roles() {
    $this->currentUserHasRole('some_role')->shouldBe(FALSE);
    $user = new EntityStub('user', NULL, ['name' => 'some_name', 'role' => 'some_role']);
    $this->setCurrentUser($user);
    $this->currentUserHasRole('some_role')->shouldBe(TRUE);
  }

}
