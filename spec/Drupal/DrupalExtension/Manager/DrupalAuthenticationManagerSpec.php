<?php

namespace spec\Drupal\DrupalExtension\Manager;

use Behat\Mink\Element\DocumentElement;
use Behat\Mink\Mink;
use Behat\Mink\Session;
use Drupal\DrupalDriverManagerInterface;
use Drupal\DrupalExtension\Manager\DrupalAuthenticationManager;
use Drupal\DrupalExtension\Manager\DrupalUserManagerInterface;
use PhpSpec\ObjectBehavior;

class DrupalAuthenticationManagerSpec extends ObjectBehavior
{
    function let(Mink $mink, DrupalUserManagerInterface $userManager, DrupalDriverManagerInterface $driverManager, Session $session)
    {
        $mink->getSession(null)->willReturn($session);
        $this->beConstructedWith($mink, $userManager, $driverManager, [], []);
    }

    function it_is_initializable()
    {
        $this->shouldHaveType(DrupalAuthenticationManager::class);
    }

    function it_can_check_login_status(Session $session, DocumentElement $page)
    {
        $this->loggedIn()->shouldBe(false);

        $page->has('css', '.a-class')->willReturn(true);
        $session->isStarted()->willReturn(true);
        $session->getPage()->willReturn($page);
        $this->setDrupalParameters(['selectors' => ['logged_in_selector' => '.a-class']]);
        $this->loggedIn()->shouldBe(true);
    }
}
