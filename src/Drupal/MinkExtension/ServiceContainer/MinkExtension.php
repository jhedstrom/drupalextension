<?php

declare(strict_types=1);

namespace Drupal\MinkExtension\ServiceContainer;

use Behat\MinkExtension\ServiceContainer\MinkExtension as BaseMinkExtension;
use Drupal\MinkExtension\ServiceContainer\Driver\BrowserKitFactory;

/**
 * Drupal Mink extension with additional browser driver support.
 */
class MinkExtension extends BaseMinkExtension {

  /**
   * {@inheritdoc}
   */
  public function __construct() {
    parent::__construct();
    $this->registerDriverFactory(new BrowserKitFactory());
  }

}
