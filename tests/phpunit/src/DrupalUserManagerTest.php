<?php

declare(strict_types=1);

namespace Drupal\DrupalExtension\Tests;

use Drupal\DrupalExtension\Manager\DrupalUserManager;
use Drupal\DrupalExtension\Manager\DrupalUserManagerInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Tests the DrupalUserManager class.
 */
#[CoversClass(DrupalUserManager::class)]
class DrupalUserManagerTest extends TestCase {

  /**
   * Tests that the manager implements the interface.
   */
  public function testImplementsInterface(): void {
    $manager = new DrupalUserManager();
    $this->assertInstanceOf(DrupalUserManagerInterface::class, $manager);
  }

  /**
   * Tests that current user defaults to false.
   */
  public function testCurrentUserDefaultsToFalse(): void {
    $manager = new DrupalUserManager();
    $this->assertFalse($manager->getCurrentUser());
  }

  /**
   * Tests setting and getting the current user.
   */
  public function testSetAndGetCurrentUser(): void {
    $manager = new DrupalUserManager();
    $user = (object) ['name' => 'admin'];
    $manager->setCurrentUser($user);
    $this->assertSame($user, $manager->getCurrentUser());
  }

  /**
   * Tests setting current user to false.
   */
  public function testSetCurrentUserToFalse(): void {
    $manager = new DrupalUserManager();
    $manager->setCurrentUser((object) ['name' => 'admin']);
    $manager->setCurrentUser(FALSE);
    $this->assertFalse($manager->getCurrentUser());
  }

  /**
   * Tests adding and getting a user.
   */
  public function testAddAndGetUser(): void {
    $manager = new DrupalUserManager();
    $user = (object) ['name' => 'editor'];
    $manager->addUser($user);
    $this->assertSame($user, $manager->getUser('editor'));
  }

  /**
   * Tests that getting an unknown user throws an exception.
   */
  public function testGetUserThrowsForUnknown(): void {
    $manager = new DrupalUserManager();
    $this->expectException(\InvalidArgumentException::class);
    $this->expectExceptionMessage('No user with ghost name is registered with the driver.');
    $manager->getUser('ghost');
  }

  /**
   * Tests removing a user.
   */
  public function testRemoveUser(): void {
    $manager = new DrupalUserManager();
    $manager->addUser((object) ['name' => 'editor']);
    $manager->removeUser('editor');
    $this->expectException(\InvalidArgumentException::class);
    $manager->getUser('editor');
  }

  /**
   * Tests that getUsers returns all registered users.
   */
  public function testGetUsersReturnsAll(): void {
    $manager = new DrupalUserManager();
    $user_a = (object) ['name' => 'alice'];
    $user_b = (object) ['name' => 'bob'];
    $manager->addUser($user_a);
    $manager->addUser($user_b);
    $users = $manager->getUsers();
    $this->assertCount(2, $users);
    $this->assertSame($user_a, $users['alice']);
    $this->assertSame($user_b, $users['bob']);
  }

  /**
   * Tests that getUsers returns empty by default.
   */
  public function testGetUsersReturnsEmptyByDefault(): void {
    $manager = new DrupalUserManager();
    $this->assertSame([], $manager->getUsers());
  }

  /**
   * Tests clearing all users.
   */
  public function testClearUsers(): void {
    $manager = new DrupalUserManager();
    $manager->setCurrentUser((object) ['name' => 'admin']);
    $manager->addUser((object) ['name' => 'editor']);
    $manager->clearUsers();
    $this->assertFalse($manager->getCurrentUser());
    $this->assertSame([], $manager->getUsers());
  }

  /**
   * Tests the hasUsers method.
   *
   * @param array<int, \stdClass> $users
   *   Users to add to the manager.
   * @param bool $expected
   *   Expected hasUsers() result.
   */
  #[DataProvider('dataProviderHasUsers')]
  public function testHasUsers(array $users, bool $expected): void {
    $manager = new DrupalUserManager();
    foreach ($users as $user) {
      $manager->addUser($user);
    }
    $this->assertSame($expected, $manager->hasUsers());
  }

  /**
   * Provides data for testHasUsers().
   */
  public static function dataProviderHasUsers(): \Iterator {
    yield 'no users' => [[], FALSE];
    yield 'one user' => [[(object) ['name' => 'alice']], TRUE];
    yield 'multiple users' => [[(object) ['name' => 'alice'], (object) ['name' => 'bob']], TRUE];
  }

  /**
   * Tests the currentUserIsAnonymous method.
   */
  #[DataProvider('dataProviderCurrentUserIsAnonymous')]
  public function testCurrentUserIsAnonymous(\stdClass|bool $user, bool $expected): void {
    $manager = new DrupalUserManager();
    $manager->setCurrentUser($user);
    $this->assertSame($expected, $manager->currentUserIsAnonymous());
  }

  /**
   * Provides data for testCurrentUserIsAnonymous().
   */
  public static function dataProviderCurrentUserIsAnonymous(): \Iterator {
    yield 'false is anonymous' => [FALSE, TRUE];
    yield 'user object is not anonymous' => [(object) ['name' => 'admin'], FALSE];
  }

  /**
   * Tests the currentUserHasRole method.
   */
  #[DataProvider('dataProviderCurrentUserHasRole')]
  public function testCurrentUserHasRole(\stdClass|bool $user, string $role, bool $expected): void {
    $manager = new DrupalUserManager();
    $manager->setCurrentUser($user);
    $this->assertSame($expected, $manager->currentUserHasRole($role));
  }

  /**
   * Provides data for testCurrentUserHasRole().
   */
  public static function dataProviderCurrentUserHasRole(): \Iterator {
    yield 'anonymous has no role' => [FALSE, 'admin', FALSE];
    yield 'user without role property' => [(object) ['name' => 'alice'], 'editor', FALSE];
    yield 'user with matching role' => [(object) ['name' => 'alice', 'role' => 'editor'], 'editor', TRUE];
    yield 'user with non-matching role' => [(object) ['name' => 'alice', 'role' => 'editor'], 'admin', FALSE];
    yield 'user with empty role' => [(object) ['name' => 'alice', 'role' => ''], 'editor', FALSE];
  }

}
