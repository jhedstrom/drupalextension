<?php

declare(strict_types=1);

namespace Drupal\DrupalExtension\Context\Annotation;

use Drupal\DrupalExtension\Hook\Call\AfterNodeCreate;
use Drupal\DrupalExtension\Hook\Call\AfterTermCreate;
use Drupal\DrupalExtension\Hook\Call\AfterUserCreate;
use Drupal\DrupalExtension\Hook\Call\BeforeNodeCreate;
use Drupal\DrupalExtension\Hook\Call\BeforeTermCreate;
use Drupal\DrupalExtension\Hook\Call\BeforeUserCreate;
use Behat\Behat\Context\Annotation\AnnotationReader;
use Drupal\DrupalExtension\Hook\Dispatcher;
use ReflectionMethod;

/**
 * Annotated contexts reader.
 *
 * @see \Behat\Behat\Context\Loader\AnnotatedLoader
 */
class Reader implements AnnotationReader
{

    private static string $regex = '/^\@(beforenodecreate|afternodecreate|beforetermcreate|aftertermcreate|beforeusercreate|afterusercreate)(?:\s+(.+))?$/i';

    /**
     * @var string[]
     */
    private static array $classes = [
        'afternodecreate' => AfterNodeCreate::class,
        'aftertermcreate' => AfterTermCreate::class,
        'afterusercreate' => AfterUserCreate::class,
        'beforenodecreate' => BeforeNodeCreate::class,
        'beforetermcreate' => BeforeTermCreate::class,
        'beforeusercreate' => BeforeUserCreate::class,
    ];

    /**
     * {@inheritdoc}
     */
    public function readCallee(mixed $contextClass, ReflectionMethod $method, mixed $docLine, mixed $description)
    {

        if (!preg_match(self::$regex, $docLine, $match)) {
            return null;
        }

        $type = strtolower($match[1]);
        $class = self::$classes[$type];
        $pattern = $match[2] ?? null;
        $callable = [$contextClass, $method->getName()];

        return new $class($pattern, $callable, $description);
    }
}
