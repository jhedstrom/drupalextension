<?php

declare(strict_types=1);

namespace Drupal\DrupalExtension\Context\Attribute;

use Behat\Behat\Context\Attribute\AttributeReader;
use Drupal\DrupalExtension\Hook\Attribute\AfterNodeCreate as AfterNodeCreateAttribute;
use Drupal\DrupalExtension\Hook\Attribute\AfterTermCreate as AfterTermCreateAttribute;
use Drupal\DrupalExtension\Hook\Attribute\AfterUserCreate as AfterUserCreateAttribute;
use Drupal\DrupalExtension\Hook\Attribute\BeforeNodeCreate as BeforeNodeCreateAttribute;
use Drupal\DrupalExtension\Hook\Attribute\BeforeTermCreate as BeforeTermCreateAttribute;
use Drupal\DrupalExtension\Hook\Attribute\BeforeUserCreate as BeforeUserCreateAttribute;
use Drupal\DrupalExtension\Hook\Attribute\DrupalHook;
use Drupal\DrupalExtension\Hook\Call\AfterNodeCreate;
use Drupal\DrupalExtension\Hook\Call\AfterTermCreate;
use Drupal\DrupalExtension\Hook\Call\AfterUserCreate;
use Drupal\DrupalExtension\Hook\Call\BeforeNodeCreate;
use Drupal\DrupalExtension\Hook\Call\BeforeTermCreate;
use Drupal\DrupalExtension\Hook\Call\BeforeUserCreate;

/**
 * Reads Drupal entity hook attributes from context methods.
 */
class HookAttributeReader implements AttributeReader {

  /**
   * Map of attribute classes to their hook call classes.
   *
   * @var array<class-string, class-string>
   */
  private const ATTRIBUTE_MAP = [
    AfterNodeCreateAttribute::class => AfterNodeCreate::class,
    AfterTermCreateAttribute::class => AfterTermCreate::class,
    AfterUserCreateAttribute::class => AfterUserCreate::class,
    BeforeNodeCreateAttribute::class => BeforeNodeCreate::class,
    BeforeTermCreateAttribute::class => BeforeTermCreate::class,
    BeforeUserCreateAttribute::class => BeforeUserCreate::class,
  ];

  /**
   * {@inheritdoc}
   */
  public function readCallees(string $contextClass, \ReflectionMethod $method): array {
    $attributes = $method->getAttributes(DrupalHook::class, \ReflectionAttribute::IS_INSTANCEOF);

    $callees = [];
    foreach ($attributes as $attribute) {
      $hookCallClass = self::ATTRIBUTE_MAP[$attribute->getName()] ?? NULL;
      if ($hookCallClass === NULL) {
        continue;
      }

      $hook = $attribute->newInstance();
      $callable = [$contextClass, $method->getName()];
      $callees[] = new $hookCallClass($hook->getFilterString(), $callable);
    }

    return $callees;
  }

}
