<?php

namespace Drupal\DrupalExtension\Manager;

use Behat\Mink\Exception\DriverException;
use Behat\Mink\Mink;
use Drupal\DrupalExtension\DrupalParametersTrait;
use Drupal\DrupalExtension\MinkAwareTrait;

/**
 * Default implementation of the Drupal authentication manager service.
 */
class DrupalAuthenticationManager implements DrupalAuthenticationManagerInterface
{

    use DrupalParametersTrait;
    use MinkAwareTrait;

    /**
     * The Drupal user manager.
     *
     * @var \Drupal\DrupalExtension\Manager\DrupalUserManagerInterface
     */
    protected $userManager;

    /**
     * Constructs a DrupalAuthenticationManager object.
     *
     * @param \Behat\Mink\Mink $mink
     *   The Mink sessions manager.
     * @param \Drupal\DrupalExtension\Manager\DrupalUserManagerInterface $drupalUserManager
     *   The Drupal user manager.
     */
    public function __construct(Mink $mink, DrupalUserManagerInterface $drupalUserManager, array $minkParameters, array $drupalParameters)
    {
        $this->setMink($mink);
        $this->userManager = $drupalUserManager;
        $this->setMinkParameters($minkParameters);
        $this->setDrupalParameters($drupalParameters);
    }

    /**
     * {@inheritdoc}
     */
    public function logIn(\stdClass $user)
    {
        // Check if we are already logged in.
        if ($this->loggedIn()) {
            $this->logout();
        }

        $this->getSession()->visit($this->locatePath('/user'));
        $element = $this->getSession()->getPage();
        $element->fillField($this->getDrupalText('username_field'), $user->name);
        $element->fillField($this->getDrupalText('password_field'), $user->pass);
        $submit = $element->findButton($this->getDrupalText('log_in'));
        if (empty($submit)) {
            throw new \Exception(sprintf("No submit button at %s", $this->getSession()->getCurrentUrl()));
        }

        // Log in.
        $submit->click();

        if (!$this->loggedIn()) {
            if (isset($user->role)) {
                throw new \Exception(sprintf("Unable to determine if logged in because 'log_out' link cannot be found for user '%s' with role '%s'", $user->name, $user->role));
            } else {
                throw new \Exception(sprintf("Unable to determine if logged in because 'log_out' link cannot be found for user '%s'", $user->name));
            }
        }

        $this->userManager->setCurrentUser($user);
    }

    /**
     * {@inheritdoc}
     */
    public function logout() {
        $this->getSession()->visit($this->locatePath('/user/logout'));
        $this->userManager->setCurrentUser(FALSE);
    }

    /**
     * {@inheritdoc}
     */
    public function loggedIn() {
        $session = $this->getSession();
        $page = $session->getPage();

        // Look for a css selector to determine if a user is logged in.
        // Default is the logged-in class on the body tag.
        // Which should work with almost any theme.
        try {
            if ($page->has('css', $this->getDrupalSelector('logged_in_selector'))) {
                return TRUE;
            }
        } catch (DriverException $e) {
            // This test may fail if the driver did not load any site yet.
        }

        // Some themes do not add that class to the body, so lets check if the
        // login form is displayed on /user/login.
        $session->visit($this->locatePath('/user/login'));
        if (!$page->has('css', $this->getDrupalSelector('login_form_selector'))) {
            return TRUE;
        }

        $session->visit($this->locatePath('/'));

        // As a last resort, if a logout link is found, we are logged in. While not
        // perfect, this is how Drupal SimpleTests currently work as well.
        if ($page->findLink($this->getDrupalText('log_out'))) {
            return TRUE;
        }

        // The user appears to be anonymous. Clear the current user from the user
        // manager so this reflects the actual situation.
        $this->userManager->setCurrentUser(FALSE);

        return FALSE;
    }
}
