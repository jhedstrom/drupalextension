<?php

namespace Drupal\DrupalExtension\Context\Environment\Reader;

use Behat\Behat\Context\Environment\UninitializedContextEnvironment;
use Behat\Behat\Context\Environment\ContextEnvironment;
use Behat\Behat\Context\Reader\ContextReader;
use Behat\Testwork\Call\Callee;
use Behat\Testwork\Environment\Environment;
use Behat\Testwork\Environment\Exception\EnvironmentReadException;
use Behat\Testwork\Environment\Reader\EnvironmentReader;

use Drupal\DrupalDriverManager;
use Drupal\Driver\SubDriverFinderInterface;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RegexIterator;

/**
 * Read in additional contexts provided by core and contrib.
 */
final class Reader implements EnvironmentReader {

  /**
   * @var ContextReader[]
   */
  private $contextReaders = array();

  /**
   * Drupal driver manager.
   *
   * @var \Drupal\DrupalDriverManager
   */
  private $drupal;

  /**
   * Configuration parameters for this suite.
   */
  private $parameters;

  /**
   * Statically cached lists of subcontexts by path.
   *
   * @var array
   */
  static protected $subContexts;

  /**
   * Register the Drupal driver manager.
   */
  public function __construct(DrupalDriverManager $drupal, array $parameters) {
    $this->drupal = $drupal;
    $this->parameters = $parameters;
  }

  /**
   * Registers context loader.
   *
   * @param ContextReader $contextReader
   */
  public function registerContextReader(ContextReader $contextReader) {
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
          get_class($environment)
        ), $environment);
    }

    $callees = array();
    if (!$environment instanceof UninitializedContextEnvironment) {
      return $callees;
    }

    $contextClasses = $this->findSubContextClasses();

    foreach ($contextClasses as $contextClass) {
      $callees = array_merge(
        $callees,
        $this->readContextCallees($environment, $contextClass)
      );

      // Register context.
      $environment->registerContextClass($contextClass, array($this->drupal));
    }

    return $callees;
  }

    /**
     * Reads callees from a specific suite's context.
     *
     * @param ContextEnvironment $environment
     * @param string             $contextClass
     *
     * @return Callee[]
     */
    private function readContextCallees(ContextEnvironment $environment, $contextClass)
    {
        $callees = array();
        foreach ($this->contextReaders as $loader) {
            $callees = array_merge(
                $callees,
                $loader->readContextCallees($environment, $contextClass)
            );
        }

        return $callees;
    }

  /**
   * Finds and loads available subcontext classes.
   */
  private function findSubContextClasses() {
    $class_names = array();

    // Initialize any available sub-contexts.
    if (isset($this->parameters['subcontexts'])) {
      $paths = array();
      // Drivers may specify paths to subcontexts.
      if ($this->parameters['subcontexts']['autoload']) {
        foreach ($this->drupal->getDrivers() as $name => $driver) {
          if ($driver instanceof SubDriverFinderInterface) {
            $paths += $driver->getSubDriverPaths();
          }
        }
      }

      // Additional subcontext locations may be specified manually in behat.yml.
      if (isset($this->parameters['subcontexts']['paths'])) {
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
        if (!$reflect->isAbstract() && $reflect->implementsInterface('Drupal\DrupalExtension\Context\DrupalSubContextInterface')) {
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
   * @return array
   *   An array of paths.
   */
  private function findAvailableSubContexts($path, $pattern = '/^.+\.behat\.inc/i') {

    if (isset(static::$subContexts[$pattern][$path])) {
      return static::$subContexts[$pattern][$path];
    }

    static::$subContexts[$pattern][$path] = array();

    $fileIterator = new RegexIterator(
      new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($path)
      ), $pattern,
      RegexIterator::MATCH
    );
    foreach ($fileIterator as $found) {
      static::$subContexts[$pattern][$path][$found->getRealPath()] = $found->getFileName();
    }

    return static::$subContexts[$pattern][$path];
  }

  /**
   * Load each subcontext file.
   *
   * @param array $subcontexts
   *   An array of files to include.
   */
  private function loadSubContexts($subcontexts) {
    foreach ($subcontexts as $path => $subcontext) {
      if (!file_exists($path)) {
        throw new \RuntimeException(sprintf('Subcontext path %s path does not exist.', $path));
      }

      // Load file.
      require_once $path;
    }
  }

}
