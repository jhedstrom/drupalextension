<?php

declare(strict_types=1);

namespace Drupal\DrupalExtension\Tests;

use PHPUnit\Framework\MockObject\MockObject;
use Behat\Mink\Driver\DriverInterface;
use Behat\Mink\Element\DocumentElement;
use Behat\Mink\Element\NodeElement;
use Behat\Mink\Exception\DriverException;
use Behat\Mink\Mink;
use Behat\Mink\Session;
use Drupal\Driver\AuthenticationDriverInterface;
use Drupal\DrupalDriverManagerInterface;
use Drupal\DrupalExtension\Manager\DrupalAuthenticationManager;
use Drupal\DrupalExtension\Manager\DrupalAuthenticationManagerInterface;
use Drupal\DrupalExtension\Manager\DrupalUserManager;
use Drupal\DrupalExtension\Manager\DrupalUserManagerInterface;
use Drupal\DrupalExtension\Manager\FastLogoutInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(DrupalAuthenticationManager::class)]
class DrupalAuthenticationManagerTest extends TestCase
{

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

    public function testImplementsInterfaces(): void
    {
        $manager = $this->createManager();
        $this->assertInstanceOf(DrupalAuthenticationManagerInterface::class, $manager);
        $this->assertInstanceOf(FastLogoutInterface::class, $manager);
    }

    public function testLogInSuccess(): void
    {
        $submit = $this->createMock(NodeElement::class);
        $submit->expects($this->once())->method('click');

        $page = $this->createMock(DocumentElement::class);
        $page->method('findButton')->with('Log in')->willReturn($submit);
        $page->method('has')->willReturn(true);

        $session = $this->createSessionMock($page);
        $session->method('isStarted')->willReturn(true); // @phpstan-ignore method.notFound

        $userManager = new DrupalUserManager();
        $driverManager = $this->createDriverManagerMock();
        $manager = $this->createManager($session, $userManager, $driverManager);

        $user = (object) ['name' => 'admin', 'pass' => 'password'];
        $manager->logIn($user);
        $this->assertSame($user, $userManager->getCurrentUser());
    }

    public function testLogInThrowsWhenNoSubmitButton(): void
    {
        $page = $this->createMock(DocumentElement::class);
        $page->method('findButton')->willReturn(null);

        $session = $this->createSessionMock($page);
        $session->method('isStarted')->willReturn(true); // @phpstan-ignore method.notFound
        $session->method('getCurrentUrl')->willReturn('http://localhost/user/login'); // @phpstan-ignore method.notFound

        $manager = $this->createManager($session);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('No submit button at http://localhost/user/login');
        $manager->logIn((object) ['name' => 'admin', 'pass' => 'pass']);
    }

    #[DataProvider('dataProviderLogInThrowsWhenNotLoggedIn')]
    public function testLogInThrowsWhenNotLoggedIn(\stdClass $user, string $expected_message): void
    {
        $submit = $this->createMock(NodeElement::class);

        $page = $this->createMock(DocumentElement::class);
        $page->method('findButton')->willReturn($submit);
        $page->method('has')->willReturn(false);
        $page->method('findLink')->willReturn(null);

        $session = $this->createSessionMock($page);
        $session->method('isStarted')->willReturn(true); // @phpstan-ignore method.notFound

        $manager = $this->createManager($session);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage($expected_message);
        $manager->logIn($user);
    }

    public static function dataProviderLogInThrowsWhenNotLoggedIn(): \Iterator
    {
        yield 'user without role' => [
            (object) ['name' => 'admin', 'pass' => 'pass'],
            "Unable to determine if logged in because 'Log out' ('log_out') link cannot be found for user 'admin'",
        ];
        yield 'user with role' => [
            (object) ['name' => 'admin', 'pass' => 'pass', 'role' => 'administrator'],
            "Unable to determine if logged in because 'Log out' ('log_out') link cannot be found for user 'admin' with role 'administrator'",
        ];
    }

    public function testLogInCallsBackendDriver(): void
    {
        $submit = $this->createMock(NodeElement::class);

        $page = $this->createMock(DocumentElement::class);
        $page->method('findButton')->willReturn($submit);
        $page->method('has')->willReturn(true);

        $session = $this->createSessionMock($page);
        $session->method('isStarted')->willReturn(true); // @phpstan-ignore method.notFound

        $authDriver = $this->createAuthDriverMock();
        $authDriver->expects($this->once())->method('login');

        $driverManager = $this->createMock(DrupalDriverManagerInterface::class);
        $driverManager->method('getDriver')->willReturn($authDriver);

        $manager = $this->createManager($session, null, $driverManager);
        $manager->logIn((object) ['name' => 'admin', 'pass' => 'pass']);
    }

    public function testLogout(): void
    {
        $page = $this->createMock(DocumentElement::class);

        $session = $this->createSessionMock($page);
        $session->expects($this->once())->method('visit'); // @phpstan-ignore method.notFound
        $session->method('getCurrentUrl')->willReturn('http://localhost/user/logout'); // @phpstan-ignore method.notFound

        $userManager = new DrupalUserManager();
        $userManager->setCurrentUser((object) ['name' => 'admin']);

        $driverManager = $this->createDriverManagerMock();
        $manager = $this->createManager($session, $userManager, $driverManager);
        $manager->logout();
        $this->assertFalse($userManager->getCurrentUser());
    }

    public function testLogoutWithConfirmationPage(): void
    {
        $submit = $this->createMock(NodeElement::class);
        $submit->expects($this->once())->method('click');

        $page = $this->createMock(DocumentElement::class);
        $page->method('findButton')->with('Log out')->willReturn($submit);

        $session = $this->createSessionMock($page);
        $session->method('getCurrentUrl')->willReturn('http://localhost/user/logout/confirm'); // @phpstan-ignore method.notFound

        $userManager = new DrupalUserManager();
        $driverManager = $this->createDriverManagerMock();
        $manager = $this->createManager($session, $userManager, $driverManager);
        $manager->logout();
        $this->assertFalse($userManager->getCurrentUser());
    }

    public function testLogoutWithConfirmationPageThrowsWhenNoButton(): void
    {
        $page = $this->createMock(DocumentElement::class);
        $page->method('findButton')->willReturn(null);

        $session = $this->createSessionMock($page);
        $session->method('getCurrentUrl')->willReturn('http://localhost/user/logout/confirm'); // @phpstan-ignore method.notFound

        $manager = $this->createManager($session);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("Unable to determine if logged out because 'Log out' button cannot be found on the logout confirmation page");
        $manager->logout();
    }

    public function testLogoutCallsBackendDriver(): void
    {
        $page = $this->createMock(DocumentElement::class);
        $session = $this->createSessionMock($page);
        $session->method('getCurrentUrl')->willReturn('http://localhost/user/logout'); // @phpstan-ignore method.notFound

        $authDriver = $this->createAuthDriverMock();
        $authDriver->expects($this->once())->method('logout');

        $driverManager = $this->createMock(DrupalDriverManagerInterface::class);
        $driverManager->method('getDriver')->willReturn($authDriver);

        $manager = $this->createManager($session, null, $driverManager);
        $manager->logout();
    }

    #[DataProvider('dataProviderLoggedIn')]
    public function testLoggedIn(bool $session_started, bool $has_logged_in_selector, bool $has_login_form, bool $has_logout_link, bool $expected): void
    {
        $page = $this->createMock(DocumentElement::class);

        $hasMap = [];
        if ($session_started) {
            $hasMap[] = ['css', 'body.logged-in', $has_logged_in_selector];
            if (!$has_logged_in_selector) {
                $hasMap[] = ['css', 'form#user-login', $has_login_form];
            }
        }
        $page->method('has')->willReturnMap($hasMap);
        $page->method('findLink')->willReturn($has_logout_link ? $this->createMock(NodeElement::class) : null);

        $session = $this->createSessionMock($page);
        $session->method('isStarted')->willReturn($session_started); // @phpstan-ignore method.notFound

        $manager = $this->createManager($session);
        $this->assertSame($expected, $manager->loggedIn());
    }

    public static function dataProviderLoggedIn(): \Iterator
    {
        yield 'session not started' => [false, false, false, false, false];
        yield 'logged in selector found' => [true, true, false, false, true];
        yield 'login form found means not logged in' => [true, false, true, false, false];
        yield 'logout link found means logged in' => [true, false, false, true, true];
        yield 'nothing found means not logged in' => [true, false, false, false, false];
    }

    public function testLoggedInReturnsFalseWhenPageNotAvailable(): void
    {
        $session = $this->createMock(Session::class);
        $session->method('isStarted')->willReturn(true);
        $session->method('getPage')->willReturn(null);

        $mink = new Mink(['default' => $session]);
        $mink->setDefaultSessionName('default');

        $manager = new DrupalAuthenticationManager($mink, new DrupalUserManager(), $this->createDriverManagerMock(), self::MINK_PARAMS, self::DRUPAL_PARAMS);
        $this->assertFalse($manager->loggedIn());
    }

    public function testLoggedInHandlesDriverException(): void
    {
        $page = $this->createMock(DocumentElement::class);
        $page->method('has')->willReturnCallback(function ($selector, $locator): true {
            if ($locator === 'body.logged-in') {
                throw new DriverException('Not loaded');
            }
            return true;
        });

        $session = $this->createSessionMock($page);
        $session->method('isStarted')->willReturn(true); // @phpstan-ignore method.notFound

        $manager = $this->createManager($session);
        // Should not throw — login form is found so returns false.
        $this->assertFalse($manager->loggedIn());
    }

    public function testFastLogoutResetsSession(): void
    {
        $session = $this->createMock(Session::class);
        $session->method('isStarted')->willReturn(true);
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

    public function testFastLogoutSkipsResetWhenNotStarted(): void
    {
        $session = $this->createMock(Session::class);
        $session->method('isStarted')->willReturn(false);
        $session->expects($this->never())->method('reset');

        $mink = new Mink(['default' => $session]);
        $mink->setDefaultSessionName('default');

        $driverManager = $this->createDriverManagerMock();
        $manager = new DrupalAuthenticationManager($mink, new DrupalUserManager(), $driverManager, self::MINK_PARAMS, self::DRUPAL_PARAMS);
        $manager->fastLogout();
    }

    public function testFastLogoutCallsBackendDriver(): void
    {
        $session = $this->createMock(Session::class);
        $session->method('isStarted')->willReturn(false);

        $mink = new Mink(['default' => $session]);
        $mink->setDefaultSessionName('default');

        $authDriver = $this->createAuthDriverMock();
        $authDriver->expects($this->once())->method('logout');

        $driverManager = $this->createMock(DrupalDriverManagerInterface::class);
        $driverManager->method('getDriver')->willReturn($authDriver);

        $manager = new DrupalAuthenticationManager($mink, new DrupalUserManager(), $driverManager, self::MINK_PARAMS, self::DRUPAL_PARAMS);
        $manager->fastLogout();
    }

    public function testGetLogoutElement(): void
    {
        $link = $this->createMock(NodeElement::class);
        $page = $this->createMock(DocumentElement::class);
        $page->method('findLink')->with('Log out')->willReturn($link);

        $session = $this->createSessionMock($page);
        $manager = $this->createManager($session);
        $this->assertSame($link, $manager->getLogoutElement());
    }

    private function createSessionMock(?DocumentElement $page = null): Session
    {
        $session = $this->createMock(Session::class);
        $session->method('getPage')->willReturn($page ?? $this->createMock(DocumentElement::class));
        return $session;
    }

    private function createAuthDriverMock(): AuthenticationDriverInterface|\Drupal\Driver\DriverInterface|MockObject
    {
        $driver = $this->createMockForIntersectionOfInterfaces([AuthenticationDriverInterface::class, \Drupal\Driver\DriverInterface::class]);
        $driver->method('isBootstrapped')->willReturn(true);
        return $driver;
    }

    private function createDriverManagerMock(): DrupalDriverManagerInterface
    {
        $driver = $this->createMock(\Drupal\Driver\DriverInterface::class);
        $driver->method('isBootstrapped')->willReturn(true);
        $driverManager = $this->createMock(DrupalDriverManagerInterface::class);
        $driverManager->method('getDriver')->willReturn($driver);
        return $driverManager;
    }

    private function createManager(?Session $session = null, ?DrupalUserManagerInterface $user_manager = null, ?DrupalDriverManagerInterface $driver_manager = null): DrupalAuthenticationManager
    {
        $session ??= $this->createSessionMock();
        $mink = new Mink(['default' => $session]);
        $mink->setDefaultSessionName('default');

        return new DrupalAuthenticationManager(
            $mink,
            $user_manager ?? new DrupalUserManager(),
            $driver_manager ?? $this->createDriverManagerMock(),
            self::MINK_PARAMS,
            self::DRUPAL_PARAMS
        );
    }
}
