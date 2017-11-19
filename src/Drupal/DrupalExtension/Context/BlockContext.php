<?php

namespace Drupal\DrupalExtension\Context;

use Behat\MinkExtension\Context\RawMinkContext;
use Behat\Behat\Context\TranslatableContext;

/**
 * Extensions to the Mink Extension.
 */
class BlockContext extends RawDrupalContext implements TranslatableContext {

  /**
   * Returns list of definition translation resources paths.
   *
   * @return array
   */
  public static function getTranslationResources() {
    return glob(__DIR__ . '/../../../../i18n/*.xliff');
  }

  /**
   * @Given I place block :delta of module :module at region :region of theme :theme
   */
  public function assertPlaceBlockNew($delta, $module, $region, $theme) {
    $this->placeBlock($delta, $module, $region, $theme);
    $this->getSession()->getPage()->find("block-$module-$delta");
  }

}
