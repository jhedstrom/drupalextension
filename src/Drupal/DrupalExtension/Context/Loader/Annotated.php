<?php

namespace Drupal\DrupalExtension\Context\Loader;

use Behat\Behat\Annotation\AnnotationInterface,
    Behat\Behat\Context\ContextInterface,
    Behat\Behat\Context\Loader\LoaderInterface,
    Behat\Behat\Hook\HookInterface;

use Drupal\DrupalExtension\Hook\Dispatcher;

/**
 * Annotated contexts reader.
 *
 * @see \Behat\Behat\Context\Loader\AnnotatedLoader
 */
class Annotated implements LoaderInterface {
  private $hookDispatcher;
  private $annotationClasses = array(
    'afternodecreate' => 'Drupal\DrupalExtension\Hook\Annotation\AfterNodeCreate',
    'aftertermcreate' => 'Drupal\DrupalExtension\Hook\Annotation\AfterTermCreate',
    'afterusercreate' => 'Drupal\DrupalExtension\Hook\Annotation\AfterUserCreate',
    'beforenodecreate' => 'Drupal\DrupalExtension\Hook\Annotation\BeforeNodeCreate',
    'beforetermcreate' => 'Drupal\DrupalExtension\Hook\Annotation\BeforeTermCreate',
    'beforeusercreate' => 'Drupal\DrupalExtension\Hook\Annotation\BeforeUserCreate',
  );
  private $availableAnnotations;

  /**
   * Initializes context loader.
   *
   * @param Dispatcher $hookDispatcher
   */
  public function __construct(Dispatcher $hookDispatcher) {
    $this->hookDispatcher       = $hookDispatcher;
    $this->availableAnnotations = implode("|", array_keys($this->annotationClasses));
  }

  /**
   * Checks if loader supports provided context.
   *
   * @param ContextInterface $context
   *
   * @return Boolean
   */
  public function supports(ContextInterface $context) {
    return TRUE;
  }

  /**
   * Loads definitions and translations from provided context.
   *
   * @param ContextInterface $context
   */
  public function load(ContextInterface $context) {
    $reflection = new \ReflectionObject($context);

    foreach ($reflection->getMethods(\ReflectionMethod::IS_PUBLIC) as $methodRefl) {
      foreach ($this->readMethodAnnotations($reflection->getName(), $methodRefl) as $annotation) {
        if ($annotation instanceof HookInterface) {
          $this->hookDispatcher->addHook($annotation);
        }
      }
    }
  }

  /**
   * Reads all supported method annotations.
   *
   * @param stirng            $className
   * @param \ReflectionMethod $method
   *
   * @return array
   */
  private function readMethodAnnotations($className, \ReflectionMethod $method) {
    $annotations = array();

    // Read parent annotations.
    try {
      $prototype = $method->getPrototype();
      $annotations = array_merge($annotations, $this->readMethodAnnotations($className, $prototype));
    } catch (\ReflectionException $e) {}

    // Read method annotations.
    if ($docBlock = $method->getDocComment()) {
      $description = NULL;

      foreach (explode("\n", $docBlock) as $docLine) {
        $docLine = preg_replace('/^\/\*\*\s*|^\s*\*\s*|\s*\*\/$|\s*$/', '', $docLine);

        if (preg_match('/^\@('.$this->availableAnnotations.')\s*(.*)?$/i', $docLine, $matches)) {
          $class    = $this->annotationClasses[strtolower($matches[1])];
          $callback = array($className, $method->getName());

          if (isset($matches[2]) && !empty($matches[2])) {
            $annotation = new $class($callback, $matches[2]);
          } else {
            $annotation = new $class($callback);
          }

          if (NULL !== $description) {
            $annotation->setDescription($description);
          }

          $annotations[] = $annotation;
        } elseif (NULL === $description && '' !== $docLine && FALSE === strpos($docLine, '@')) {
          $description = $docLine;
        }
      }
    }

    return $annotations;
  }
}
