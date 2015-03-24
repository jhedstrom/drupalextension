<?php

/**
 * @file
 * Contains \Drupal\behat_test\Config\BehatTestExtensionInstallStorage
 */

namespace Drupal\behat_test\Config;

use Drupal\Core\Config\ExtensionInstallStorage;
use Drupal\Core\Config\StorageInterface;

class BehatTestExtensionInstallStorage extends ExtensionInstallStorage {

  /**
   * {@inheritdoc}
   */
  public function __construct(StorageInterface $config_storage, $directory = self::CONFIG_INSTALL_DIRECTORY, $collection = StorageInterface::DEFAULT_COLLECTION, $include_profile = TRUE) {
    parent::__construct($config_storage, $directory, $collection, $include_profile);

    $this->directory = 'override_config';
  }


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
