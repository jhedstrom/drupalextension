<?php

namespace spec\Drupal\DrupalExtension\Manager;

use Behat\Mink\Element\DocumentElement;
use Behat\Mink\Mink;
use Behat\Mink\Session;
use Drupal\DrupalDriverManagerInterface;
use Drupal\DrupalExtension\Manager\DrupalAuthenticationManager;
use Drupal\DrupalExtension\Manager\DrupalUserManagerInterface;
use PhpSpec\ObjectBehavior;

/**
 * Tests the DrupalAuthenticationManager class.
 */
class DrupalAuthenticationManagerSpec extends ObjectBehavior {

  public function let(Mink $mink, DrupalUserManagerInterface $userManager, DrupalDriverManagerInterface $driverManager, Session $session) {
    $mink->getSession(NULL)->willReturn($session);
    $this->beConstructedWith($mink, $userManager, $driverManager, [], []);
  }

  public function it_is_initializable() {
    $this->shouldHaveType(DrupalAuthenticationManager::class);
  }

  public function it_can_check_login_status(Session $session, DocumentElement $page) {
    $this->loggedIn()->shouldBe(FALSE);

    $page->has('css', '.a-class')->willReturn(TRUE);
    $session->isStarted()->willReturn(TRUE);
    $session->getPage()->willReturn($page);
    $this->setDrupalParameters(['selectors' => ['logged_in_selector' => '.a-class']]);
    $this->loggedIn()->shouldBe(TRUE);
  }

}
