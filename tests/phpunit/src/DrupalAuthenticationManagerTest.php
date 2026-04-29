<?php

declare(strict_types=1);

namespace Drupal\DrupalExtension\Tests;

use Drupal\Driver\DriverInterface;
use Drupal\Driver\Entity\EntityStub;
use Drupal\Driver\Entity\EntityStubInterface;
use PHPUnit\Framework\MockObject\MockObject;
use Behat\Mink\Driver\DriverInterface as MinkDriverInterface;
use Behat\Mink\Element\DocumentElement;
use Behat\Mink\Element\NodeElement;
use Behat\Mink\Exception\DriverException;
use Behat\Mink\Mink;
use Behat\Mink\Session;
use Drupal\Driver\Capability\AuthenticationCapabilityInterface;
use Drupal\DrupalDriverManagerInterface;
use Drupal\DrupalExtension\Manager\DrupalAuthenticationManager;
use Drupal\DrupalExtension\Manager\DrupalAuthenticationManagerInterface;
use Drupal\DrupalExtension\Manager\DrupalUserManager;
use Drupal\DrupalExtension\Manager\DrupalUserManagerInterface;
use Drupal\DrupalExtension\Manager\FastLogoutInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Tests for DrupalAuthenticationManager.
 */
#[CoversClass(DrupalAuthenticationManager::class)]
class DrupalAuthenticationManagerTest extends TestCase {

  private const DRUPAL_PARAMS = [
    'text' => [
      'log_in' => 'Log in',
      'log_out' => 'Log out',
      'login_url' => '/user/login',
      'logout_url' => '/user/logout',
      'logout_confirm_url' => '/user/logout/confirm',
      'username_field' => 'Username',
      'password_field' => 'Password',
    ],
    'selectors' => [
      'logged_in_selector' => 'body.logged-in',
      'login_form_selector' => 'form#user-login',
    ],
  ];

  private const MINK_PARAMS = [
    'base_url' => 'http://localhost',
  ];

  /**
   * Tests that the manager implements the expected interfaces.
   */
  public function testImplementsInterfaces(): void {
    $manager = $this->createManager();
    $this->assertInstanceOf(DrupalAuthenticationManagerInterface::class, $manager);
    $this->assertInstanceOf(FastLogoutInterface::class, $manager);
  }

  /**
   * Tests that logIn() succeeds when a submit button is found.
   */
  public function testLogInSuccess(): void {
    $submit = $this->createMock(NodeElement::class);
    $submit->expects($this->once())->method('click');

    $page = $this->createMock(DocumentElement::class);
    $page->method('findButton')->with('Log in')->willReturn($submit);
    $page->method('has')->willReturn(TRUE);

    $session = $this->createSessionMock($page);
    // @phpstan-ignore method.notFound
    $session->method('isStarted')->willReturn(TRUE);

    $user_manager = new DrupalUserManager();
    $driver_manager = $this->createDriverManagerMock();
    $manager = $this->createManager($session, $user_manager, $driver_manager);

    $user = new EntityStub('user', NULL, ['name' => 'admin', 'pass' => 'password']);
    $manager->logIn($user);
    $this->assertSame($user, $user_manager->getCurrentUser());
  }

  /**
   * Tests that logIn() throws when no submit button is found.
   */
  public function testLogInThrowsWhenNoSubmitButton(): void {
    $page = $this->createMock(DocumentElement::class);
    $page->method('findButton')->willReturn(NULL);

    $session = $this->createSessionMock($page);
    // @phpstan-ignore method.notFound
    $session->method('isStarted')->willReturn(TRUE);
    // @phpstan-ignore method.notFound
    $session->method('getCurrentUrl')->willReturn('http://localhost/user/login');

    $manager = $this->createManager($session);

    $this->expectException(\Exception::class);
    $this->expectExceptionMessage('Submit button matching css "login form" not found.');
    $manager->logIn(new EntityStub('user', NULL, ['name' => 'admin', 'pass' => 'pass']));
  }

  /**
   * Tests that logIn() throws when the user is not actually logged in.
   */
  #[DataProvider('dataProviderLogInThrowsWhenNotLoggedIn')]
  public function testLogInThrowsWhenNotLoggedIn(EntityStubInterface $user, string $expected_message): void {
    $submit = $this->createMock(NodeElement::class);

    $page = $this->createMock(DocumentElement::class);
    $page->method('findButton')->willReturn($submit);
    $page->method('has')->willReturn(FALSE);
    $page->method('findLink')->willReturn(NULL);

    $session = $this->createSessionMock($page);
    // @phpstan-ignore method.notFound
    $session->method('isStarted')->willReturn(TRUE);

    $manager = $this->createManager($session);

    $this->expectException(\Exception::class);
    $this->expectExceptionMessage($expected_message);
    $manager->logIn($user);
  }

  /**
   * Data provider for testLogInThrowsWhenNotLoggedIn().
   */
  public static function dataProviderLogInThrowsWhenNotLoggedIn(): \Iterator {
    yield 'user without role' => [
      new EntityStub('user', NULL, ['name' => 'admin', 'pass' => 'pass']),
      "Unable to determine if logged in because 'Log out' ('log_out') link cannot be found for user 'admin'",
    ];
    yield 'user with role' => [
      new EntityStub('user', NULL, ['name' => 'admin', 'pass' => 'pass', 'role' => 'administrator']),
      "Unable to determine if logged in because 'Log out' ('log_out') link cannot be found for user 'admin' with role 'administrator'",
    ];
  }

  /**
   * Tests that logIn() calls the backend authentication driver.
   */
  public function testLogInCallsBackendDriver(): void {
    $submit = $this->createMock(NodeElement::class);

    $page = $this->createMock(DocumentElement::class);
    $page->method('findButton')->willReturn($submit);
    $page->method('has')->willReturn(TRUE);

    $session = $this->createSessionMock($page);
    // @phpstan-ignore method.notFound
    $session->method('isStarted')->willReturn(TRUE);

    $auth_driver = $this->createAuthDriverMock();
    $auth_driver->expects($this->once())->method('login');

    $driver_manager = $this->createMock(DrupalDriverManagerInterface::class);
    $driver_manager->method('getDriver')->willReturn($auth_driver);

    $manager = $this->createManager($session, NULL, $driver_manager);
    $manager->logIn(new EntityStub('user', NULL, ['name' => 'admin', 'pass' => 'pass']));
  }

  /**
   * Tests that logout() visits the logout URL and clears the current user.
   */
  public function testLogout(): void {
    $page = $this->createMock(DocumentElement::class);

    $session = $this->createSessionMock($page);
    // @phpstan-ignore method.notFound
    $session->expects($this->once())->method('visit');
    // @phpstan-ignore method.notFound
    $session->method('getCurrentUrl')->willReturn('http://localhost/user/logout');

    $user_manager = new DrupalUserManager();
    $user_manager->setCurrentUser(new EntityStub('user', NULL, ['name' => 'admin']));

    $driver_manager = $this->createDriverManagerMock();
    $manager = $this->createManager($session, $user_manager, $driver_manager);
    $manager->logout();
    $this->assertFalse($user_manager->getCurrentUser());
  }

  /**
   * Tests that logout() handles the confirmation page by clicking the button.
   */
  public function testLogoutWithConfirmationPage(): void {
    $submit = $this->createMock(NodeElement::class);
    $submit->expects($this->once())->method('click');

    $page = $this->createMock(DocumentElement::class);
    $page->method('findButton')->with('Log out')->willReturn($submit);

    $session = $this->createSessionMock($page);
    // @phpstan-ignore method.notFound
    $session->method('getCurrentUrl')->willReturn('http://localhost/user/logout/confirm');

    $user_manager = new DrupalUserManager();
    $driver_manager = $this->createDriverManagerMock();
    $manager = $this->createManager($session, $user_manager, $driver_manager);
    $manager->logout();
    $this->assertFalse($user_manager->getCurrentUser());
  }

  /**
   * Tests that logout() throws when the confirmation page has no button.
   */
  public function testLogoutWithConfirmationPageThrowsWhenNoButton(): void {
    $page = $this->createMock(DocumentElement::class);
    $page->method('findButton')->willReturn(NULL);

    $session = $this->createSessionMock($page);
    // @phpstan-ignore method.notFound
    $session->method('getCurrentUrl')->willReturn('http://localhost/user/logout/confirm');

    $manager = $this->createManager($session);

    $this->expectException(\Exception::class);
    $this->expectExceptionMessage('Logout button matching css "logout confirmation page" not found.');
    $manager->logout();
  }

  /**
   * Tests that logout() calls the backend authentication driver.
   */
  public function testLogoutCallsBackendDriver(): void {
    $page = $this->createMock(DocumentElement::class);
    $session = $this->createSessionMock($page);
    // @phpstan-ignore method.notFound
    $session->method('getCurrentUrl')->willReturn('http://localhost/user/logout');

    $auth_driver = $this->createAuthDriverMock();
    $auth_driver->expects($this->once())->method('logout');

    $driver_manager = $this->createMock(DrupalDriverManagerInterface::class);
    $driver_manager->method('getDriver')->willReturn($auth_driver);

    $manager = $this->createManager($session, NULL, $driver_manager);
    $manager->logout();
  }

  /**
   * Tests the loggedIn() method across various session and page states.
   */
  #[DataProvider('dataProviderLoggedIn')]
  public function testLoggedIn(bool $session_started, bool $has_logged_in_selector, bool $has_login_form, bool $has_logout_link, bool $expected): void {
    $page = $this->createMock(DocumentElement::class);

    $has_map = [];
    if ($session_started) {
      $has_map[] = ['css', 'body.logged-in', $has_logged_in_selector];
      if (!$has_logged_in_selector) {
        $has_map[] = ['css', 'form#user-login', $has_login_form];
      }
    }
    $page->method('has')->willReturnMap($has_map);
    $page->method('findLink')->willReturn($has_logout_link ? $this->createMock(NodeElement::class) : NULL);

    $session = $this->createSessionMock($page);
    // @phpstan-ignore method.notFound
    $session->method('isStarted')->willReturn($session_started);

    $manager = $this->createManager($session);
    $this->assertSame($expected, $manager->loggedIn());
  }

  /**
   * Data provider for testLoggedIn().
   */
  public static function dataProviderLoggedIn(): \Iterator {
    yield 'session not started' => [FALSE, FALSE, FALSE, FALSE, FALSE];
    yield 'logged in selector found' => [TRUE, TRUE, FALSE, FALSE, TRUE];
    yield 'login form found means not logged in' => [TRUE, FALSE, TRUE, FALSE, FALSE];
    yield 'logout link found means logged in' => [TRUE, FALSE, FALSE, TRUE, TRUE];
    yield 'nothing found means not logged in' => [TRUE, FALSE, FALSE, FALSE, FALSE];
  }

  /**
   * Tests that loggedIn() returns FALSE when the page is not available.
   */
  public function testLoggedInReturnsFalseWhenPageNotAvailable(): void {
    $session = $this->createMock(Session::class);
    $session->method('isStarted')->willReturn(TRUE);
    $session->method('getPage')->willReturn(NULL);

    $mink = new Mink(['default' => $session]);
    $mink->setDefaultSessionName('default');

    $manager = new DrupalAuthenticationManager($mink, new DrupalUserManager(), $this->createDriverManagerMock(), self::MINK_PARAMS, self::DRUPAL_PARAMS);
    $this->assertFalse($manager->loggedIn());
  }

  /**
   * Tests that loggedIn() handles a DriverException gracefully.
   */
  public function testLoggedInHandlesDriverException(): void {
    $page = $this->createMock(DocumentElement::class);
    $page->method('has')->willReturnCallback(function ($selector, $locator): true {
      if ($locator === 'body.logged-in') {
            throw new DriverException('Not loaded');
      }
        return TRUE;
    });

    $session = $this->createSessionMock($page);
    // @phpstan-ignore method.notFound
    $session->method('isStarted')->willReturn(TRUE);

    $manager = $this->createManager($session);
    // Should not throw — login form is found so returns false.
    $this->assertFalse($manager->loggedIn());
  }

  /**
   * Tests that fastLogout() resets the session and clears the current user.
   */
  public function testFastLogoutResetsSession(): void {
    $session = $this->createMock(Session::class);
    $session->method('isStarted')->willReturn(TRUE);
    $session->expects($this->once())->method('reset');

    $mink = new Mink(['default' => $session]);
    $mink->setDefaultSessionName('default');

    $user_manager = new DrupalUserManager();
    $user_manager->setCurrentUser(new EntityStub('user', NULL, ['name' => 'admin']));

    $driver_manager = $this->createDriverManagerMock();
    $manager = new DrupalAuthenticationManager($mink, $user_manager, $driver_manager, self::MINK_PARAMS, self::DRUPAL_PARAMS);
    $manager->fastLogout();

    $this->assertFalse($user_manager->getCurrentUser());
  }

  /**
   * Tests fastLogout() skips session reset when the session is not started.
   */
  public function testFastLogoutSkipsResetWhenNotStarted(): void {
    $session = $this->createMock(Session::class);
    $session->method('isStarted')->willReturn(FALSE);
    $session->expects($this->never())->method('reset');

    $mink = new Mink(['default' => $session]);
    $mink->setDefaultSessionName('default');

    $driver_manager = $this->createDriverManagerMock();
    $manager = new DrupalAuthenticationManager($mink, new DrupalUserManager(), $driver_manager, self::MINK_PARAMS, self::DRUPAL_PARAMS);
    $manager->fastLogout();
  }

  /**
   * Tests that fastLogout() calls the backend authentication driver.
   */
  public function testFastLogoutCallsBackendDriver(): void {
    $session = $this->createMock(Session::class);
    $session->method('isStarted')->willReturn(FALSE);

    $mink = new Mink(['default' => $session]);
    $mink->setDefaultSessionName('default');

    $auth_driver = $this->createAuthDriverMock();
    $auth_driver->expects($this->once())->method('logout');

    $driver_manager = $this->createMock(DrupalDriverManagerInterface::class);
    $driver_manager->method('getDriver')->willReturn($auth_driver);

    $manager = new DrupalAuthenticationManager($mink, new DrupalUserManager(), $driver_manager, self::MINK_PARAMS, self::DRUPAL_PARAMS);
    $manager->fastLogout();
  }

  /**
   * Tests that getLogoutElement() returns the logout link element.
   */
  public function testGetLogoutElement(): void {
    $link = $this->createMock(NodeElement::class);
    $page = $this->createMock(DocumentElement::class);
    $page->method('findLink')->with('Log out')->willReturn($link);

    $session = $this->createSessionMock($page);
    $manager = $this->createManager($session);
    $this->assertSame($link, $manager->getLogoutElement());
  }

  /**
   * Creates a Session mock with the given page.
   */
  private function createSessionMock(?DocumentElement $page = NULL): Session {
    $session = $this->createMock(Session::class);
    $session->method('getPage')->willReturn($page ?? $this->createMock(DocumentElement::class));
    $session->method('getDriver')->willReturn($this->createMock(MinkDriverInterface::class));
    return $session;
  }

  /**
   * Creates a mock for the AuthenticationCapability and DriverInterface.
   *
   * @return \Drupal\Driver\Capability\AuthenticationCapabilityInterface&\Drupal\Driver\DriverInterface&\PHPUnit\Framework\MockObject\MockObject
   *   The mocked driver.
   */
  private function createAuthDriverMock(): AuthenticationCapabilityInterface&DriverInterface&MockObject {
    /** @var \Drupal\Driver\Capability\AuthenticationCapabilityInterface&\Drupal\Driver\DriverInterface&\PHPUnit\Framework\MockObject\MockObject $driver */
    $driver = $this->createMockForIntersectionOfInterfaces([
      AuthenticationCapabilityInterface::class,
      DriverInterface::class,
    ]);
    $driver->method('isBootstrapped')->willReturn(TRUE);
    return $driver;
  }

  /**
   * Creates a DrupalDriverManagerInterface mock with a bootstrapped driver.
   */
  private function createDriverManagerMock(): DrupalDriverManagerInterface {
    $driver = $this->createMock(DriverInterface::class);
    $driver->method('isBootstrapped')->willReturn(TRUE);
    $driver_manager = $this->createMock(DrupalDriverManagerInterface::class);
    $driver_manager->method('getDriver')->willReturn($driver);
    return $driver_manager;
  }

  /**
   * Tests that logIn() skips waiting when login_wait is 0.
   */
  public function testLogInSkipsWaitWhenLoginWaitIsZero(): void {
    $submit = $this->createMock(NodeElement::class);

    $page = $this->createMock(DocumentElement::class);
    $page->method('findButton')->with('Log in')->willReturn($submit);
    $page->method('has')->willReturn(TRUE);

    $session = $this->createSessionMock($page);
    // @phpstan-ignore method.notFound
    $session->method('isStarted')->willReturn(TRUE);
    // getCurrentUrl should never be called for wait purposes when disabled.
    // @phpstan-ignore method.notFound
    $session->method('getCurrentUrl')->willReturn('http://localhost/user/login');

    $params = self::DRUPAL_PARAMS;
    $params['login_wait'] = 0;

    $manager = $this->createManager($session, NULL, NULL, $params);
    $manager->logIn(new EntityStub('user', NULL, ['name' => 'admin', 'pass' => 'password']));
  }

  /**
   * Tests that logIn() waits for the logged-in selector when login_wait > 0.
   */
  public function testLogInWaitsForLoggedInSelector(): void {
    $submit = $this->createMock(NodeElement::class);

    $call_count = 0;
    $page = $this->createMock(DocumentElement::class);
    $page->method('findButton')->with('Log in')->willReturn($submit);
    // Simulate: logged_in_selector not found on first call, found on second.
    $page->method('has')->willReturnCallback(function (string $selector, string $locator) use (&$call_count): bool {
      if ($locator === 'body.logged-in') {
        $call_count++;
        // First two calls return FALSE (during wait loop and loggedIn check),
        // then return TRUE.
        return $call_count > 2;
      }
      return FALSE;
    });
    $page->method('find')->willReturnCallback(function (string $selector, string $locator) use ($page): ?DocumentElement {
      if ($locator === 'body') {
        return $page;
      }
      return NULL;
    });

    $url_call_count = 0;
    $session = $this->createSessionMock($page);
    // @phpstan-ignore method.notFound
    $session->method('isStarted')->willReturn(TRUE);
    // Simulate URL change after login (redirect).
    // @phpstan-ignore method.notFound
    $session->method('getCurrentUrl')->willReturnCallback(function () use (&$url_call_count): string {
      $url_call_count++;
      return $url_call_count <= 1 ? 'http://localhost/user/login' : 'http://localhost/user/1';
    });

    $params = self::DRUPAL_PARAMS;
    $params['login_wait'] = 1;

    $user_manager = new DrupalUserManager();
    $manager = $this->createManager($session, $user_manager, NULL, $params);
    $manager->logIn(new EntityStub('user', NULL, ['name' => 'admin', 'pass' => 'password']));

    $this->assertNotFalse($user_manager->getCurrentUser());
  }

  /**
   * Tests that logIn() without login_wait throws when selector is delayed.
   *
   * Demonstrates the race condition: without login_wait, a delayed
   * logged_in_selector causes login to fail even though login succeeded.
   */
  public function testLogInFailsWithoutLoginWaitWhenSelectorDelayed(): void {
    $submit = $this->createMock(NodeElement::class);

    $page = $this->createMock(DocumentElement::class);
    $page->method('findButton')->willReturnCallback(fn(string $text): ?NodeElement => $text === 'Log in' ? $submit : NULL);
    // logged_in_selector is never found (simulates slow JS).
    $page->method('has')->willReturn(FALSE);
    $page->method('findLink')->willReturn(NULL);

    $session = $this->createSessionMock($page);
    // @phpstan-ignore method.notFound
    $session->method('isStarted')->willReturn(TRUE);
    // @phpstan-ignore method.notFound
    $session->method('getCurrentUrl')->willReturn('http://localhost/user/1');

    // No login_wait configured — the race condition scenario.
    $manager = $this->createManager($session);

    $this->expectException(\Exception::class);
    $this->expectExceptionMessage("Unable to determine if logged in");
    $manager->logIn(new EntityStub('user', NULL, ['name' => 'admin', 'pass' => 'password']));
  }

  /**
   * Creates a DrupalAuthenticationManager with optional overrides.
   *
   * @param \Behat\Mink\Session|null $session
   *   Optional Mink session override.
   * @param \Drupal\DrupalExtension\Manager\DrupalUserManagerInterface|null $user_manager
   *   Optional user manager override.
   * @param \Drupal\DrupalDriverManagerInterface|null $driver_manager
   *   Optional driver manager override.
   * @param array<string, mixed>|null $drupal_params
   *   Optional Drupal parameters override.
   */
  private function createManager(?Session $session = NULL, ?DrupalUserManagerInterface $user_manager = NULL, ?DrupalDriverManagerInterface $driver_manager = NULL, ?array $drupal_params = NULL): DrupalAuthenticationManager {
    $session ??= $this->createSessionMock();
    $mink = new Mink(['default' => $session]);
    $mink->setDefaultSessionName('default');

    return new DrupalAuthenticationManager(
          $mink,
          $user_manager ?? new DrupalUserManager(),
          $driver_manager ?? $this->createDriverManagerMock(),
          self::MINK_PARAMS,
          $drupal_params ?? self::DRUPAL_PARAMS
      );
  }

}
