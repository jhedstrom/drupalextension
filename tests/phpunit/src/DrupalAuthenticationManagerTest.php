<?php

declare(strict_types=1);

namespace Drupal\DrupalExtension\Tests;

use Drupal\Driver\DriverInterface;
use PHPUnit\Framework\MockObject\MockObject;
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

    $userManager = new DrupalUserManager();
    $driverManager = $this->createDriverManagerMock();
    $manager = $this->createManager($session, $userManager, $driverManager);

    $user = (object) ['name' => 'admin', 'pass' => 'password'];
    $manager->logIn($user);
    $this->assertSame($user, $userManager->getCurrentUser());
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
    $this->expectExceptionMessage('No submit button at http://localhost/user/login');
    $manager->logIn((object) ['name' => 'admin', 'pass' => 'pass']);
  }

  /**
   * Tests that logIn() throws when the user is not actually logged in.
   */
  #[DataProvider('dataProviderLogInThrowsWhenNotLoggedIn')]
  public function testLogInThrowsWhenNotLoggedIn(\stdClass $user, string $expected_message): void {
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
      (object) ['name' => 'admin', 'pass' => 'pass'],
      "Unable to determine if logged in because 'Log out' ('log_out') link cannot be found for user 'admin'",
    ];
    yield 'user with role' => [
      (object) ['name' => 'admin', 'pass' => 'pass', 'role' => 'administrator'],
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

    $authDriver = $this->createAuthDriverMock();
    $authDriver->expects($this->once())->method('login');

    $driverManager = $this->createMock(DrupalDriverManagerInterface::class);
    $driverManager->method('getDriver')->willReturn($authDriver);

    $manager = $this->createManager($session, NULL, $driverManager);
    $manager->logIn((object) ['name' => 'admin', 'pass' => 'pass']);
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

    $userManager = new DrupalUserManager();
    $userManager->setCurrentUser((object) ['name' => 'admin']);

    $driverManager = $this->createDriverManagerMock();
    $manager = $this->createManager($session, $userManager, $driverManager);
    $manager->logout();
    $this->assertFalse($userManager->getCurrentUser());
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

    $userManager = new DrupalUserManager();
    $driverManager = $this->createDriverManagerMock();
    $manager = $this->createManager($session, $userManager, $driverManager);
    $manager->logout();
    $this->assertFalse($userManager->getCurrentUser());
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
    $this->expectExceptionMessage("Unable to determine if logged out because 'Log out' button cannot be found on the logout confirmation page");
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

    $authDriver = $this->createAuthDriverMock();
    $authDriver->expects($this->once())->method('logout');

    $driverManager = $this->createMock(DrupalDriverManagerInterface::class);
    $driverManager->method('getDriver')->willReturn($authDriver);

    $manager = $this->createManager($session, NULL, $driverManager);
    $manager->logout();
  }

  /**
   * Tests the loggedIn() method across various session and page states.
   */
  #[DataProvider('dataProviderLoggedIn')]
  public function testLoggedIn(bool $session_started, bool $has_logged_in_selector, bool $has_login_form, bool $has_logout_link, bool $expected): void {
    $page = $this->createMock(DocumentElement::class);

    $hasMap = [];
    if ($session_started) {
      $hasMap[] = ['css', 'body.logged-in', $has_logged_in_selector];
      if (!$has_logged_in_selector) {
        $hasMap[] = ['css', 'form#user-login', $has_login_form];
      }
    }
    $page->method('has')->willReturnMap($hasMap);
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

    $userManager = new DrupalUserManager();
    $userManager->setCurrentUser((object) ['name' => 'admin']);

    $driverManager = $this->createDriverManagerMock();
    $manager = new DrupalAuthenticationManager($mink, $userManager, $driverManager, self::MINK_PARAMS, self::DRUPAL_PARAMS);
    $manager->fastLogout();

    $this->assertFalse($userManager->getCurrentUser());
  }

  /**
   * Tests that fastLogout() skips session reset when the session is not started.
   */
  public function testFastLogoutSkipsResetWhenNotStarted(): void {
    $session = $this->createMock(Session::class);
    $session->method('isStarted')->willReturn(FALSE);
    $session->expects($this->never())->method('reset');

    $mink = new Mink(['default' => $session]);
    $mink->setDefaultSessionName('default');

    $driverManager = $this->createDriverManagerMock();
    $manager = new DrupalAuthenticationManager($mink, new DrupalUserManager(), $driverManager, self::MINK_PARAMS, self::DRUPAL_PARAMS);
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

    $authDriver = $this->createAuthDriverMock();
    $authDriver->expects($this->once())->method('logout');

    $driverManager = $this->createMock(DrupalDriverManagerInterface::class);
    $driverManager->method('getDriver')->willReturn($authDriver);

    $manager = new DrupalAuthenticationManager($mink, new DrupalUserManager(), $driverManager, self::MINK_PARAMS, self::DRUPAL_PARAMS);
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
    return $session;
  }

  /**
   * Creates a mock implementing both AuthenticationCapabilityInterface and DriverInterface.
   */
  private function createAuthDriverMock(): AuthenticationCapabilityInterface|DriverInterface|MockObject {
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
    $driverManager = $this->createMock(DrupalDriverManagerInterface::class);
    $driverManager->method('getDriver')->willReturn($driver);
    return $driverManager;
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
    $manager->logIn((object) ['name' => 'admin', 'pass' => 'password']);

    // If we get here without hanging, the wait was skipped.
    $this->assertTrue(TRUE);
  }

  /**
   * Tests that logIn() waits for the logged-in selector when login_wait > 0.
   */
  public function testLogInWaitsForLoggedInSelector(): void {
    $submit = $this->createMock(NodeElement::class);

    $callCount = 0;
    $page = $this->createMock(DocumentElement::class);
    $page->method('findButton')->with('Log in')->willReturn($submit);
    // Simulate: logged_in_selector not found on first call, found on second.
    $page->method('has')->willReturnCallback(function (string $selector, string $locator) use (&$callCount): bool {
      if ($locator === 'body.logged-in') {
        $callCount++;
        // First two calls return FALSE (during wait loop and loggedIn check),
        // then return TRUE.
        return $callCount > 2;
      }
      return FALSE;
    });
    $page->method('find')->willReturnCallback(function (string $selector, string $locator) use ($page): ?DocumentElement {
      if ($locator === 'body') {
        return $page;
      }
      return NULL;
    });

    $urlCallCount = 0;
    $session = $this->createSessionMock($page);
    // @phpstan-ignore method.notFound
    $session->method('isStarted')->willReturn(TRUE);
    // Simulate URL change after login (redirect).
    // @phpstan-ignore method.notFound
    $session->method('getCurrentUrl')->willReturnCallback(function () use (&$urlCallCount): string {
      $urlCallCount++;
      return $urlCallCount <= 1 ? 'http://localhost/user/login' : 'http://localhost/user/1';
    });

    $params = self::DRUPAL_PARAMS;
    $params['login_wait'] = 1;

    $userManager = new DrupalUserManager();
    $manager = $this->createManager($session, $userManager, NULL, $params);
    $manager->logIn((object) ['name' => 'admin', 'pass' => 'password']);

    $this->assertNotFalse($userManager->getCurrentUser());
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
    $manager->logIn((object) ['name' => 'admin', 'pass' => 'password']);
  }

  /**
   * Creates a DrupalAuthenticationManager with optional overrides.
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
