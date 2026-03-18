<?php

declare(strict_types=1);

namespace Drupal\DrupalExtension;

use Behat\Mink\Mink;

/**
 * Provides helpful methods to interact with the Mink session.
 *
 * This is making the functionality from RawMinkContext available for reuse in
 * classes that do not extend it.
 *
 * @see \Behat\MinkExtension\Context\RawMinkContext
 */
trait MinkAwareTrait {

  /**
   * The Mink sessions manager.
   *
   * @var \Behat\Mink\Mink
   */
  protected $mink;

  /**
   * The parameters for the Mink extension.
   *
   * @var array
   */
  protected $minkParameters;

  /**
   * Sets the Mink sessions manager.
   *
   * @param \Behat\Mink\Mink $mink
   *   The Mink sessions manager.
   */
  public function setMink(Mink $mink): void {
    $this->mink = $mink;
  }

  /**
   * Returns the Mink sessions manager.
   *
   * @return \Behat\Mink\Mink
   *   The Mink sessions manager.
   */
  public function getMink() {
    return $this->mink;
  }

  /**
   * Returns the Mink session.
   *
   * @param string|null $name
   *   The name of the session to return. If omitted the active session will
   *   be returned.
   *
   * @return \Behat\Mink\Session
   *   The Mink session.
   */
  public function getSession(?string $name = NULL) {
    return $this->getMink()->getSession($name);
  }

  /**
   * Returns the parameters provided for Mink.
   *
   * @return array
   *   An array of Mink parameters.
   */
  public function getMinkParameters() {
    return $this->minkParameters;
  }

  /**
   * Sets parameters provided for Mink.
   */
  public function setMinkParameters(array $parameters): void {
    $this->minkParameters = $parameters;
  }

  /**
   * Returns a specific mink parameter.
   */
  public function getMinkParameter(string $name): mixed {
    return $this->minkParameters[$name] ?? NULL;
  }

  /**
   * Applies the given parameter to the Mink configuration.
   *
   * Consider that this will only be applied in scope of the class that is
   * using this trait.
   *
   * @param string $name
   *   The key of the parameter.
   * @param string $value
   *   The value of the parameter.
   */
  public function setMinkParameter(string $name, mixed $value): void {
    $this->minkParameters[$name] = $value;
  }

  /**
   * Returns Mink session assertion tool.
   *
   * @param string|null $name
   *   The name of the session to return. If omitted the active session will
   *   be returned.
   *
   * @return \Behat\Mink\WebAssert
   *   The session assertion tool.
   */
  public function assertSession(?string $name = NULL) {
    return $this->getMink()->assertSession($name);
  }

  /**
   * Visits provided relative path using provided or default session.
   */
  public function visitPath(string $path, ?string $sessionName = NULL): void {
    $this->getSession($sessionName)->visit($this->locatePath($path));
  }

  /**
   * Locates url, based on provided path.
   *
   * Override to provide custom routing mechanism.
   */
  public function locatePath(string $path): string {
    $startUrl = rtrim($this->getMinkParameter('base_url'), '/') . '/';

    return str_starts_with($path, 'http') ? $path : $startUrl . ltrim($path, '/');
  }

  /**
   * Save a screenshot of the current window to the file system.
   *
   * @param string $filename
   *   Desired filename, defaults to <browser>_<ISO 8601 date>_<randomId>.png.
   * @param string $filepath
   *   Desired filepath, defaults to upload_tmp_dir, falls back to
   *   sys_get_temp_dir().
   */
  public function saveScreenshot(?string $filename = NULL, ?string $filepath = NULL): void {
    // Under Cygwin, uniqid with more_entropy must be set to true.
    // No effect in other environments.
    $filename = $filename ?: sprintf('%s_%s_%s.%s', $this->getMinkParameter('browser_name'), date('c'), uniqid('', TRUE), 'png');
    $filepath = $filepath ?: ((ini_get('upload_tmp_dir') ?: sys_get_temp_dir()));
    file_put_contents($filepath . '/' . $filename, $this->getSession()->getScreenshot());
  }

}
