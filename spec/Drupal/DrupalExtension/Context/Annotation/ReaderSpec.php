<?php

namespace spec\Drupal\DrupalExtension\Context\Annotation;

use Drupal\DrupalExtension\Context\Annotation\Reader;
use Drupal\DrupalExtension\Hook\Call\AfterUserCreate;
use Drupal\DrupalExtension\Hook\Call\BeforeNodeCreate;
use PhpSpec\ObjectBehavior;
use ReflectionMethod;

class ReaderSpec extends ObjectBehavior
{
    function it_is_initializable()
    {
        $this->shouldHaveType(Reader::class);
    }

    function it_returns_null_for_non_matching_annotation()
    {
        $context = new class {
            public static function hook(): void
            {
            }
        };
        $method = new ReflectionMethod($context, 'hook');
        $this->readCallee($context::class, $method, '@Given something', 'desc')
            ->shouldReturn(null);
    }

    function it_reads_before_node_create_with_callable()
    {
        $context = new class {
            public static function hook(): void
            {
            }
        };
        $method = new ReflectionMethod($context, 'hook');
        $this->readCallee($context::class, $method, '@BeforeNodeCreate', 'desc')
            ->shouldBeAnInstanceOf(BeforeNodeCreate::class);
    }

    function it_reads_after_user_create_with_callable()
    {
        $context = new class {
            public static function hook(): void
            {
            }
        };
        $method = new ReflectionMethod($context, 'hook');
        $this->readCallee($context::class, $method, '@AfterUserCreate', 'desc')
            ->shouldBeAnInstanceOf(AfterUserCreate::class);
    }

    function it_reads_hook_with_filter_string()
    {
        $context = new class {
            public static function hook(): void
            {
            }
        };
        $method = new ReflectionMethod($context, 'hook');
        $callee = $this->readCallee($context::class, $method, '@BeforeNodeCreate article', 'desc');
        $callee->shouldBeAnInstanceOf(BeforeNodeCreate::class);
        $callee->getFilterString()->shouldReturn('article');
    }

    function it_reads_annotation_case_insensitively()
    {
        $context = new class {
            public static function hook(): void
            {
            }
        };
        $method = new ReflectionMethod($context, 'hook');
        $this->readCallee($context::class, $method, '@beforenodecreate', 'desc')
            ->shouldBeAnInstanceOf(BeforeNodeCreate::class);
    }
}
