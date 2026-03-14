<?php

declare(strict_types=1);

namespace Drupal\DrupalExtension\Tests;

use Drupal\DrupalExtension\Manager\DrupalUserManager;
use Drupal\DrupalExtension\Manager\DrupalUserManagerInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(DrupalUserManager::class)]
class DrupalUserManagerTest extends TestCase
{

    public function testImplementsInterface(): void
    {
        $manager = new DrupalUserManager();
        $this->assertInstanceOf(DrupalUserManagerInterface::class, $manager);
    }

    public function testCurrentUserDefaultsToFalse(): void
    {
        $manager = new DrupalUserManager();
        $this->assertFalse($manager->getCurrentUser());
    }

    public function testSetAndGetCurrentUser(): void
    {
        $manager = new DrupalUserManager();
        $user = (object) ['name' => 'admin'];
        $manager->setCurrentUser($user);
        $this->assertSame($user, $manager->getCurrentUser());
    }

    public function testSetCurrentUserToFalse(): void
    {
        $manager = new DrupalUserManager();
        $manager->setCurrentUser((object) ['name' => 'admin']);
        $manager->setCurrentUser(false);
        $this->assertFalse($manager->getCurrentUser());
    }

    public function testAddAndGetUser(): void
    {
        $manager = new DrupalUserManager();
        $user = (object) ['name' => 'editor'];
        $manager->addUser($user);
        $this->assertSame($user, $manager->getUser('editor'));
    }

    public function testGetUserThrowsForUnknown(): void
    {
        $manager = new DrupalUserManager();
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('No user with ghost name is registered with the driver.');
        $manager->getUser('ghost');
    }

    public function testRemoveUser(): void
    {
        $manager = new DrupalUserManager();
        $manager->addUser((object) ['name' => 'editor']);
        $manager->removeUser('editor');
        $this->expectException(\InvalidArgumentException::class);
        $manager->getUser('editor');
    }

    public function testGetUsersReturnsAll(): void
    {
        $manager = new DrupalUserManager();
        $userA = (object) ['name' => 'alice'];
        $userB = (object) ['name' => 'bob'];
        $manager->addUser($userA);
        $manager->addUser($userB);
        $users = $manager->getUsers();
        $this->assertCount(2, $users);
        $this->assertSame($userA, $users['alice']);
        $this->assertSame($userB, $users['bob']);
    }

    public function testGetUsersReturnsEmptyByDefault(): void
    {
        $manager = new DrupalUserManager();
        $this->assertSame([], $manager->getUsers());
    }

    public function testClearUsers(): void
    {
        $manager = new DrupalUserManager();
        $manager->setCurrentUser((object) ['name' => 'admin']);
        $manager->addUser((object) ['name' => 'editor']);
        $manager->clearUsers();
        $this->assertFalse($manager->getCurrentUser());
        $this->assertSame([], $manager->getUsers());
    }

    #[DataProvider('dataProviderHasUsers')]
    public function testHasUsers(array $users, bool $expected): void
    {
        $manager = new DrupalUserManager();
        foreach ($users as $user) {
            $manager->addUser($user);
        }
        $this->assertSame($expected, $manager->hasUsers());
    }

    public static function dataProviderHasUsers(): \Iterator
    {
        yield 'no users' => [[], false];
        yield 'one user' => [[(object) ['name' => 'alice']], true];
        yield 'multiple users' => [[(object) ['name' => 'alice'], (object) ['name' => 'bob']], true];
    }

    #[DataProvider('dataProviderCurrentUserIsAnonymous')]
    public function testCurrentUserIsAnonymous(\stdClass|bool $user, bool $expected): void
    {
        $manager = new DrupalUserManager();
        $manager->setCurrentUser($user);
        $this->assertSame($expected, $manager->currentUserIsAnonymous());
    }

    public static function dataProviderCurrentUserIsAnonymous(): \Iterator
    {
        yield 'false is anonymous' => [false, true];
        yield 'user object is not anonymous' => [(object) ['name' => 'admin'], false];
    }

    #[DataProvider('dataProviderCurrentUserHasRole')]
    public function testCurrentUserHasRole(\stdClass|bool $user, string $role, bool $expected): void
    {
        $manager = new DrupalUserManager();
        $manager->setCurrentUser($user);
        $this->assertSame($expected, $manager->currentUserHasRole($role));
    }

    public static function dataProviderCurrentUserHasRole(): \Iterator
    {
        yield 'anonymous has no role' => [false, 'admin', false];
        yield 'user without role property' => [(object) ['name' => 'alice'], 'editor', false];
        yield 'user with matching role' => [(object) ['name' => 'alice', 'role' => 'editor'], 'editor', true];
        yield 'user with non-matching role' => [(object) ['name' => 'alice', 'role' => 'editor'], 'admin', false];
        yield 'user with empty role' => [(object) ['name' => 'alice', 'role' => ''], 'editor', false];
    }
}
