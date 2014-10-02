<?php
/**
 * @file
 * Provide Behat step-definitions as Drupal Extension subcontexts.
 */

use Drupal\DrupalExtension\Context\DrupalSubContextInterface;
use Behat\Behat\Context\BehatContext;

class BeanslideSubContext extends BehatContext implements 
  DrupalSubContextInterface {
  public static function getAlias() {
    return 'beanslide';
  }
