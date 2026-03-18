<?php

declare(strict_types=1);

namespace Drupal\DrupalExtension\Context\Environment\Reader;

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
   * @var array
   */
  protected static $subContexts;

  /**
   * Register the Drupal driver manager.
   */
  public function __construct(
    /**
     * Drupal driver manager.
     */
    private readonly DrupalDriverManager $drupalDriverManager,
    /**
     * Configuration parameters for this suite.
     */
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

    $contextClasses = $this->findSubContextClasses();

    foreach ($contextClasses as $contextClass) {
      // When executing test scenarios with an examples table the registering of
      // contexts is handled differently in newer version of Behat. Starting
      // with Behat 3.2.0 the contexts are already registered, and the callees
      // are returned by the default reader.
      // Work around this and provide compatibility with Behat 3.1.0 as well as
      // 3.2.0 and higher by checking if the class already exists before
      // registering it and returning the callees.
      // @see https://github.com/Behat/Behat/issues/758
      if (!$environment->hasContextClass($contextClass)) {
        $callees = array_merge(
          $callees,
          $this->readContextCallees($environment, $contextClass)
        );

        // Register context.
        $environment->registerContextClass($contextClass, [$this->drupalDriverManager]);
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
    foreach ($this->contextReaders as $contextReader) {
      $callees = array_merge(
        $callees,
        $contextReader->readContextCallees($environment, $contextClass)
      );
    }

    return $callees;
  }

  /**
   * Finds and loads available subcontext classes.
   *
   * @return class-string[]
   *   An array of fully-qualified class names.
   */
  protected function findSubContextClasses(): array {
    $classNames = [];

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
            'The `subcontexts.paths` parameter is deprecated in Drupal Behat Extension 4.0.0 and will be removed in 4.1.0. Normal Behat contexts should be used instead and loaded via behat.yml.',
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
        if (!$reflect->isAbstract() && $reflect->implementsInterface(DrupalSubContextInterface::class)) {
          @trigger_error('Sub-contexts are deprecated in Drupal Behat Extension 4.0.0 and will be removed in 4.1.0. Class ' . $class . ' is a subcontext. This logic should be moved to a normal Behat context and loaded via behat.yml.', E_USER_DEPRECATED);
          $classNames[] = $class;
        }
      }
    }

    return $classNames;
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

    $fileIterator = new \RegexIterator(
      new \RecursiveIteratorIterator(
        new \RecursiveDirectoryIterator($path)
      ),
      $pattern,
      \RegexIterator::MATCH
    );
    foreach ($fileIterator as $found) {
      self::$subContexts[$pattern][$path][$found->getRealPath()] = $found->getFileName();
    }

    return self::$subContexts[$pattern][$path];
  }

  /**
   * Load each subcontext file.
   *
   * @param array $subcontexts
   *   An array of files to include.
   */
  protected function loadSubContexts($subcontexts): void {
    foreach ($subcontexts as $path => $subcontext) {
      if (!file_exists($path)) {
        throw new \RuntimeException(sprintf('Subcontext path %s path does not exist.', $path));
      }

      // Load file.
      require_once $path;
    }
  }

}
