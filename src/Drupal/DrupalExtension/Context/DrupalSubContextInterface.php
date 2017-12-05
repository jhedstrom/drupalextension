<?php

/**
 * Contains \Drupal\DrupalExtension\Context\DrupalSubContextInterface.
 */

namespace Drupal\DrupalExtension\Context;

use Behat\Behat\Context\Context;
use Drupal\DrupalDriverManager;

/**
 * Interface for subcontexts.
 *
 * Implement this interface if you want to provide custom Behat step definitions
 * for your contributed modules. The class should be placed in a file named
 * 'MYMODULE.behat.inc'.
 *
 * See the documentation on "Contributed module subcontexts".
 */
interface DrupalSubContextInterface extends Context
{

  /**
   * Instantiates the subcontext.
   *
   * @param \Drupal\DrupalDriverManager $drupal
   *   The Drupal Driver manager.
   */
    public function __construct(DrupalDriverManager $drupal);
}
