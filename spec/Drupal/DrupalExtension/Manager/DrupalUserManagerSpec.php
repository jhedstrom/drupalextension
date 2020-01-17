<?php

namespace spec\Drupal\DrupalExtension\Manager;

use Drupal\DrupalExtension\Manager\DrupalUserManager;
use PhpSpec\ObjectBehavior;

class DrupalUserManagerSpec extends ObjectBehavior
{
    function it_is_initializable()
    {
        $this->shouldHaveType(DrupalUserManager::class);
    }

    function it_can_set_and_get_the_current_user()
    {
        $user = new \stdClass();
        $user->name = 'some_name';
        $this->setCurrentUser($user);
        $this->getCurrentUser()->shouldBe($user);
    }

    function it_can_add_and_remove_users()
    {
        $user = new \stdClass();
        $user->name = 'some_name';
        $this->addUser($user);
        $this->getUser('some_name')->shouldBe($user);
        $this->removeUser('some_name');
        $this->shouldThrow(\InvalidArgumentException::class)->duringGetUser('some_name');
    }

    function it_can_get_all_registered_users()
    {
        $this->hasUsers()->shouldBe(false);
        $user = new \stdClass();
        $user->name = 'some_name';
        $this->addUser($user);
        $this->hasUsers()->shouldBe(true);
        $this->getUsers()->shouldBe(['some_name' => $user]);
    }

    function it_can_determine_anonymous_users()
    {
        $this->currentUserIsAnonymous()->shouldBe(true);
        $user = new \stdClass();
        $user->name = 'some_name';
        $this->setCurrentUser($user);
        $this->currentUserIsAnonymous()->shouldBe(false);
    }

    function it_can_check_roles()
    {
        $this->currentUserHasRole('some_role')->shouldBe(false);
        $user = new \stdClass();
        $user->name = 'some_name';
        $user->role = 'some_role';
        $this->setCurrentUser($user);
        $this->currentUserHasRole('some_role')->shouldBe(true);
    }
}
