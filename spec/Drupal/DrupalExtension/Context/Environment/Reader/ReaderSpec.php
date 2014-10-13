<?php

namespace spec\Drupal\DrupalExtension\Context\Environment\Reader;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

use Drupal\DrupalDriverManager;

class ReaderSpec extends ObjectBehavior
{
    function let(DrupalDriverManager $drupal)
    {
        $parameters = array();
        $this->beConstructedWith($drupal, $parameters);
    }

    function it_is_initializable()
    {
        $this->shouldHaveType('Drupal\DrupalExtension\Context\Environment\Reader\Reader');
    }

}
