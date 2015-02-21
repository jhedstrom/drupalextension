<?php

/**
 * @file
 * Contains \Drupal\behat_test\Config\BehatTestExtensionInstallStorage
 */

namespace Drupal\behat_test\Config;

use Drupal\Core\Config\ExtensionInstallStorage;

class BehatTestExtensionInstallStorage extends ExtensionInstallStorage {

  /**
   * {@inheritdoc}
   */
  protected function getAllFolders() {
    if (!isset($this->folders)) {
      $this->folders = $this->getComponentNames('module', array('behat_test'));
    }
    return $this->folders;
  }
}
