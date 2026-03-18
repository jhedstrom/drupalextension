<?php

declare(strict_types=1);

namespace Drupal\DrupalExtension\Context\ContextClass;

use Behat\Behat\Context\ContextClass\ClassGenerator as BehatClassGenerator;
use Behat\Testwork\Suite\Suite;

/**
 * Generates a starting class that extends the RawDrupalContext.
 */
class ClassGenerator implements BehatClassGenerator {

  /**
   * Template for generated context class files.
   *
   * @var string
   */
  protected static $template = <<<'PHP'
<?php

{namespace}use Drupal\DrupalExtension\Context\RawDrupalContext;
use Behat\Gherkin\Node\PyStringNode;
use Behat\Gherkin\Node\TableNode;
use Behat\Behat\Tester\Exception\PendingException;

/**
 * Defines application features from the specific context.
 */
class {className} extends RawDrupalContext {

  /**
   * Initializes context.
   *
   * Every scenario gets its own context instance.
   * You can also pass arbitrary arguments to the
   * context constructor through behat.yml.
   */
  public function __construct() {
  }

}

PHP;

  /**
   * {@inheritdoc}
   */
  public function supportsSuiteAndClass(Suite $suite, mixed $contextClass) {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function generateClass(Suite $suite, mixed $contextClass) {
    $fqn = $contextClass;

    $namespace = '';
    if (FALSE !== $pos = strrpos($fqn, '\\')) {
      $namespace = 'namespace ' . substr($fqn, 0, $pos) . ";\n\n";
      $contextClass = substr($fqn, $pos + 1);
    }

    return strtr(
          static::$template,
          [
            '{namespace}' => $namespace,
            '{className}' => $contextClass,
          ]
      );
  }

}
