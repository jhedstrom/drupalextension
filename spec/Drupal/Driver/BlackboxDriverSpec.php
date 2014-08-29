<?php

namespace spec\Drupal\Driver;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class BlackboxDriverSpec extends ObjectBehavior
{
    function it_is_initializable()
    {
        $this->shouldHaveType('Drupal\Driver\BlackboxDriver');
    }

    function it_is_always_bootstrapped()
    {
        $this->isBootStrapped()->shouldReturn(TRUE);
    }

    function it_should_not_allow_api_methods()
    {
        $user = $node = $term = new \stdClass();
        $this->shouldThrow('Drupal\Exception\UnsupportedDriverActionException')->duringUserCreate($user);
        $this->shouldThrow('Drupal\Exception\UnsupportedDriverActionException')->duringCreateNode($node);
        $this->shouldThrow('Drupal\Exception\UnsupportedDriverActionException')->duringCreateTerm($term);
    }
}
