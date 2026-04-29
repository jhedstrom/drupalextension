<?php

namespace spec\Drupal\DrupalExtension\Hook\Scope;

use Behat\Behat\Context\Context;
use Behat\Testwork\Environment\Environment;

use Behat\Testwork\Suite\Suite;
use Drupal\Driver\Entity\EntityStub;
use Drupal\Driver\Entity\EntityStubInterface;
use Drupal\DrupalExtension\Hook\Scope\BeforeNodeCreateScope;
use PhpSpec\ObjectBehavior;

/**
 * Tests the BeforeNodeCreateScope class.
 */
class BeforeNodeCreateScopeSpec extends ObjectBehavior {

  public function let(Environment $environment, Context $context, Suite $suite) {
    $stub = new EntityStub('node', 'article');
    $environment->getSuite()->willReturn($suite);
    $this->beConstructedWith($environment, $context, $stub);
  }

  public function it_is_initializable() {
    $this->shouldHaveType(BeforeNodeCreateScope::class);
  }

  public function it_should_return_context() {
    $context = $this->getContext();
    $context->shouldBeAnInstanceOf(Context::class);
  }

  public function it_should_return_a_stub() {
    $this->getStub()->shouldBeAnInstanceOf(EntityStubInterface::class);
  }

  public function it_should_return_environment() {
    $this->getEnvironment()->shouldBeAnInstanceOf(Environment::class);
  }

  public function it_should_return_suite() {
    $this->getSuite()->shouldBeAnInstanceOf(Suite::class);
  }

}
