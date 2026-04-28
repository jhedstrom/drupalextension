<?php

declare(strict_types=1);

namespace Drupal\DrupalExtension\Context\Environment\Reader;

use Behat\Behat\Context\Context;
use Behat\Behat\Context\Environment\UninitializedContextEnvironment;
use Behat\Behat\Context\Environment\ContextEnvironment;
use Behat\Behat\Context\Reader\ContextReader;
use Behat\Testwork\Environment\Environment;
use Behat\Testwork\Environment\Exception\EnvironmentReadException;
use Behat\Testwork\Environment\Reader\EnvironmentReader;

use Drupal\DrupalDriverManager;
use Drupal\Driver\SubDriverFinderInterface;

use Drupal\DrupalExtension\Context\DrupalSubContextInterface;

/**
 * Read in additional contexts provided by core and contrib.
 */
final class Reader implements EnvironmentReader {

  /**
   * Registered context readers.
   *
   * @var \Behat\Behat\Context\Reader\ContextReader[]
   */
  private array $contextReaders = [];

  /**
   * Statically cached lists of subcontexts by path.
   *
   * @var array<string, array<string, array<string, string>>>
   */
  protected static array $subContexts = [];

  /**
   * Register the Drupal driver manager.
   *
   * @param \Drupal\DrupalDriverManager $drupalDriverManager
   *   Drupal driver manager.
   * @param array<string, mixed> $parameters
   *   Configuration parameters for this suite.
   */
  public function __construct(
    private readonly DrupalDriverManager $drupalDriverManager,
    private array $parameters,
  ) {
  }

  /**
   * Registers context loader.
   */
  public function registerContextReader(ContextReader $contextReader): void {
    $this->contextReaders[] = $contextReader;
  }

  /**
   * {@inheritdoc}
   */
  public function supportsEnvironment(Environment $environment) {
    return $environment instanceof ContextEnvironment;
  }

  /**
   * {@inheritdoc}
   */
  public function readEnvironmentCallees(Environment $environment) {

    if (!$environment instanceof ContextEnvironment) {
      throw new EnvironmentReadException(sprintf(
        'ContextEnvironmentReader does not support `%s` environment.',
        $environment::class
      ), $environment);
    }

    $callees = [];
    if (!$environment instanceof UninitializedContextEnvironment) {
      return $callees;
    }

    $context_classes = $this->findSubContextClasses();

    foreach ($context_classes as $context_class) {
      // When executing test scenarios with an examples table the registering of
      // contexts is handled differently in newer version of Behat. Starting
      // with Behat 3.2.0 the contexts are already registered, and the callees
      // are returned by the default reader.
      // Work around this and provide compatibility with Behat 3.1.0 as well as
      // 3.2.0 and higher by checking if the class already exists before
      // registering it and returning the callees.
      // @see https://github.com/Behat/Behat/issues/758
      if (!$environment->hasContextClass($context_class)) {
        $callees = array_merge(
          $callees,
          $this->readContextCallees($environment, $context_class)
        );

        // Register context.
        $environment->registerContextClass($context_class, [$this->drupalDriverManager]);
      }
    }

    return $callees;
  }

  /**
   * Reads callees from a specific suite's context.
   *
   * @return \Behat\Testwork\Call\Callee[]
   *   An array of callees.
   */
  protected function readContextCallees(ContextEnvironment $environment, string $contextClass): array {
    $callees = [];
    foreach ($this->contextReaders as $context_reader) {
      $callees = array_merge(
        $callees,
        $context_reader->readContextCallees($environment, $contextClass)
      );
    }

    return $callees;
  }

  /**
   * Finds and loads available subcontext classes.
   *
   * @return array<int, class-string<Context>>
   *   An array of fully-qualified class names.
   */
  protected function findSubContextClasses(): array {
    $class_names = [];

    // Initialize any available sub-contexts.
    if (isset($this->parameters['subcontexts'])) {
      $paths = [];
      // Drivers may specify paths to subcontexts.
      if ($this->parameters['subcontexts']['autoload']) {
        foreach ($this->drupalDriverManager->getDrivers() as $driver) {
          if ($driver instanceof SubDriverFinderInterface) {
            $paths += $driver->getSubDriverPaths();
          }
        }
      }

      // Additional subcontext locations may be specified manually in behat.yml.
      if (isset($this->parameters['subcontexts']['paths'])) {
        if (!empty($this->parameters['subcontexts']['paths'])) {
          @trigger_error(
            'The "subcontexts.paths" parameter is deprecated in drupalextension:4.0.0 and is removed from drupalextension:4.1.0. Normal Behat contexts should be used instead and loaded via behat.yml. See https://www.drupal.org/project/drupalextension/issues/676',
            E_USER_DEPRECATED
          );
        }
        $paths = array_merge($paths, $this->parameters['subcontexts']['paths']);
      }

      // Load each class.
      foreach ($paths as $path) {
        if ($subcontexts = $this->findAvailableSubContexts($path)) {
          $this->loadSubContexts($subcontexts);
        }
      }

      // Find all subcontexts, excluding abstract base classes.
      $classes = get_declared_classes();
      foreach ($classes as $class) {
        $reflect = new \ReflectionClass($class);
        if (!$reflect->isAbstract() && $reflect->implementsInterface(DrupalSubContextInterface::class) && is_subclass_of($class, Context::class)) {
          @trigger_error('Sub-context support is deprecated in drupalextension:4.0.0 and is removed from drupalextension:4.1.0. Class ' . $class . ' is a sub-context. This logic should be moved to a normal Behat context and loaded via behat.yml. See https://www.drupal.org/project/drupalextension/issues/676', E_USER_DEPRECATED);
          $class_names[] = $class;
        }
      }
    }

    return $class_names;
  }

  /**
   * Find Sub-contexts matching a given pattern located at the passed path.
   *
   * @param string $path
   *   Absolute path to the directory to search for sub-contexts.
   * @param string $pattern
   *   File pattern to match. Defaults to `/^.+\.behat\.inc/i`.
   *
   * @return string[]
   *   An array of file paths keyed by real path.
   */
  protected function findAvailableSubContexts(string $path, string $pattern = '/^.+\.behat\.inc/i') {

    if (isset(self::$subContexts[$pattern][$path])) {
      return self::$subContexts[$pattern][$path];
    }

    self::$subContexts[$pattern][$path] = [];

    $file_iterator = new \RegexIterator(
      new \RecursiveIteratorIterator(
        new \RecursiveDirectoryIterator($path)
      ),
      $pattern,
      \RegexIterator::MATCH
    );
    foreach ($file_iterator as $found) {
      self::$subContexts[$pattern][$path][$found->getRealPath()] = $found->getFileName();
    }

    return self::$subContexts[$pattern][$path];
  }

  /**
   * Load each subcontext file.
   *
   * @param array<string, string> $subcontexts
   *   An array of files to include.
   */
  protected function loadSubContexts(array $subcontexts): void {
    foreach ($subcontexts as $path => $subcontext) {
      if (!file_exists($path)) {
        throw new \RuntimeException(sprintf('Subcontext path %s path does not exist.', $path));
      }

      // Load file.
      require_once $path;
    }
  }

}
