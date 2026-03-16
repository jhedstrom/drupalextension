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

/**
 * Annotated contexts reader.
 *
 * @deprecated in drupalextension:5.3.0 and is removed from drupalextension:6.0.0.
 *   Use PHP 8 attributes from Drupal\DrupalExtension\Hook\Attribute\ instead.
 *
 * @see \Behat\Behat\Context\Loader\AnnotatedLoader
 */
class Reader implements AnnotationReader {

  /**
   * Regular expression for matching supported annotation types.
   */
  private static string $regex = '/^\@(beforenodecreate|afternodecreate|beforetermcreate|aftertermcreate|beforeusercreate|afterusercreate)(?:\s+(.+))?$/i';

  /**
   * Map of annotation names to their hook call class names.
   *
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
  public function readCallee(mixed $contextClass, \ReflectionMethod $method, mixed $docLine, mixed $description) {

    if (!preg_match(self::$regex, $docLine, $match)) {
      return NULL;
    }

    $type = strtolower($match[1]);
    $class = self::$classes[$type];
    $pattern = $match[2] ?? NULL;
    $callable = [$contextClass, $method->getName()];

    return new $class($pattern, $callable, $description);
  }

}
