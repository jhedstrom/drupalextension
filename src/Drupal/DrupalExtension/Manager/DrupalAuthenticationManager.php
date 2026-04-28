<?php

declare(strict_types=1);

namespace Drupal\DrupalExtension\Manager;

use Behat\Mink\Element\NodeElement;
use Behat\Mink\Element\DocumentElement;
use Behat\Mink\Exception\DriverException;
use Behat\Mink\Mink;
use Drupal\Driver\Capability\AuthenticationCapabilityInterface;
use Drupal\DrupalDriverManagerInterface;
use Drupal\DrupalExtension\DrupalParametersTrait;
use Drupal\DrupalExtension\MinkAwareTrait;

/**
 * Default implementation of the Drupal authentication manager service.
 */
class DrupalAuthenticationManager implements DrupalAuthenticationManagerInterface, FastLogoutInterface {

  use DrupalParametersTrait;
  use MinkAwareTrait;

  /**
   * Constructs a DrupalAuthenticationManager object.
   *
   * @param \Behat\Mink\Mink $mink
   *   The Mink instance.
   * @param \Drupal\DrupalExtension\Manager\DrupalUserManagerInterface $userManager
   *   The Drupal user manager.
   * @param \Drupal\DrupalDriverManagerInterface $driverManager
   *   The Drupal driver manager.
   * @param array<string, mixed> $minkParameters
   *   Mink configuration parameters.
   * @param array<string, mixed> $drupalParameters
   *   Drupal configuration parameters.
   */
  public function __construct(
    Mink $mink,
    protected DrupalUserManagerInterface $userManager,
    protected DrupalDriverManagerInterface $driverManager,
    array $minkParameters,
    array $drupalParameters,
  ) {
    $this->setMink($mink);
    $this->setMinkParameters($minkParameters);
    $this->setDrupalParameters($drupalParameters);
  }

  /**
   * {@inheritdoc}
   */
  public function logIn(\stdClass $user): void {
    // Log out any existing user before logging in a new user.
    $this->fastLogout();

    $session = $this->getSession();

    // Navigate to the login page.
    $login_url = $this->locatePath($this->getDrupalText('login_url'));
    $session->visit($login_url);

    // Fill in the login form credentials.
    $page = $session->getPage();
    $page->fillField($this->getDrupalText('username_field'), $user->name);
    $page->fillField($this->getDrupalText('password_field'), $user->pass);

    // Submit the login form.
    $login_element = $this->getLoginElement($page);
    if (!$login_element instanceof NodeElement) {
      throw new \Exception(sprintf('No submit button at %s', $session->getCurrentUrl()));
    }
    $login_element->click();

    // Wait for the browser to load after login, if configured.
    $login_wait = $this->getDrupalParameter('login_wait');
    if ($login_wait > 0) {
      // Wait for URL change after login (redirect away from login form).
      $timeout = microtime(TRUE) + $login_wait;
      while (microtime(TRUE) < $timeout && $session->getCurrentUrl() === $login_url) {
        usleep(100000);
      }

      // Wait for page body to render.
      $timeout = microtime(TRUE) + $login_wait;
      while (microtime(TRUE) < $timeout && !$session->getPage()->find('css', 'body')) {
        usleep(100000);
      }

      // Wait for the logged-in selector to appear (may be added by JS/AJAX).
      $timeout = microtime(TRUE) + $login_wait;
      while (microtime(TRUE) < $timeout && !$session->getPage()->has('css', $this->getDrupalSelector('logged_in_selector'))) {
        usleep(100000);
      }
    }

    // Verify the login was successful.
    if (!$this->loggedIn()) {
      throw new \Exception(isset($user->role)
            ? sprintf("Unable to determine if logged in because '%s' ('log_out') link cannot be found for user '%s' with role '%s'", $this->getDrupalText('log_out'), $user->name, $user->role)
            : sprintf("Unable to determine if logged in because '%s' ('log_out') link cannot be found for user '%s'", $this->getDrupalText('log_out'), $user->name));
    }

    // Track the logged-in user.
    $this->userManager->setCurrentUser($user);

    // Log in on the backend.
    $this->backendLogin($user);
  }

  /**
   * {@inheritdoc}
   */
  public function logout(): void {
    $session = $this->getSession();

    $logout_url = $this->locatePath($this->getDrupalText('logout_url'));
    $logout_confirm_url = $this->locatePath($this->getDrupalText('logout_confirm_url'));

    $session->visit($logout_url);

    // Check to see if the user is on the logout confirm page (10.3+).
    if ($session->getCurrentUrl() === $logout_confirm_url) {
      $logout_element = $this->getLogoutConfirmElement($session->getPage());

      if (!$logout_element instanceof NodeElement) {
        throw new \Exception(sprintf("Unable to determine if logged out because '%s' button cannot be found on the logout confirmation page at %s", $this->getDrupalText('log_out'), $session->getCurrentUrl()));
      }

      $logout_element->click();
    }

    // Reset the currently tracked user.
    $this->userManager->setCurrentUser(FALSE);

    // Log out on the backend.
    $this->backendLogout();
  }

  /**
   * {@inheritdoc}
   */
  public function loggedIn(): bool {
    $session = $this->getSession();

    // If the session has not been started, then there is no user logged in.
    if (!$session->isStarted()) {
      return FALSE;
    }

    // If the page is not available, then there is no user logged in.
    // Using a nullsafe call here so PHPStan does not flag the non-nullable
    // 'getPage()' return type while still allowing test doubles that return
    // 'NULL' to short-circuit safely.
    $page = $session->getPage();
    // @phpstan-ignore identical.alwaysFalse
    if ($page === NULL) {
      return FALSE;
    }

    // Look for a CSS selector to determine if a user is logged in.
    // Default is the logged-in class on the body tag, which should work
    // with almost any theme.
    try {
      if ($page->has('css', $this->getDrupalSelector('logged_in_selector'))) {
        return TRUE;
      }
    }
    catch (DriverException) {
      // This may fail if the driver did not load any site yet.
    }

    // Some themes do not add that class to the body, so check if the login
    // form is displayed (defaults to /user/login).
    $login_url = $this->locatePath($this->getDrupalText('login_url'));
    $session->visit($login_url);
    if ($page->has('css', $this->getDrupalSelector('login_form_selector'))) {
      $this->fastLogout();
      return FALSE;
    }

    // As a last resort, if a logout link is found, we are logged in.
    // While not perfect, this is how Drupal SimpleTests currently work as
    // well.
    $session->visit($this->locatePath('/'));
    if ($this->getLogoutElement() instanceof NodeElement) {
      return TRUE;
    }

    // The user appears to be anonymous - fully reset the session to ensure
    // to prevent any issues with a partially logged-in session state.
    $this->fastLogout();

    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function fastLogout(): void {
    // Reset the session.
    $session = $this->getSession();
    if ($session->isStarted()) {
      $session->reset();
    }

    // Reset the currently tracked user.
    $this->userManager->setCurrentUser(FALSE);

    // Log out on the backend.
    $this->backendLogout();
  }

  /**
   * Get the login element from the page.
   */
  protected function getLoginElement(DocumentElement $element): ?NodeElement {
    // @todo v6: Rename `log_in` to `login_text`.
    return $element->findButton($this->getDrupalText('log_in'));
  }

  /**
   * Get the logout element from the page.
   */
  public function getLogoutElement(): ?NodeElement {
    // @todo v6: Rename `log_out` to `logout_text`.
    return $this->getSession()->getPage()->findLink($this->getDrupalText('log_out'));
  }

  /**
   * Get the logout confirm element from the page.
   */
  protected function getLogoutConfirmElement(DocumentElement $element): ?NodeElement {
    // @todo v6: Rename `log_out` to `logout_text`.
    return $element->findButton($this->getDrupalText('log_out'));
  }

  /**
   * Log in on the backend driver if it supports authentication.
   */
  protected function backendLogin(\stdClass $user): void {
    $driver = $this->driverManager->getDriver();
    if ($driver instanceof AuthenticationCapabilityInterface) {
      $driver->login($user);
    }
  }

  /**
   * Log out on the backend driver if it supports authentication.
   */
  protected function backendLogout(): void {
    $driver = $this->driverManager->getDriver();
    if ($driver instanceof AuthenticationCapabilityInterface) {
      $driver->logout();
    }
  }

}
