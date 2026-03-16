<?php

declare(strict_types=1);

namespace Drupal\behat_test\EventSubscriber;

use Drupal\Core\Config\ConfigCrudEvent;
use Drupal\Core\Config\ConfigEvents;
use Drupal\Core\State\StateInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Tracks config save events for testing config change detection.
 *
 * Records whether ConfigCrudEvent::isChanged() returns TRUE when config is
 * saved. Used to verify that cleanConfig() properly resets the config cache
 * so that change detection works correctly.
 *
 * @see https://github.com/jhedstrom/drupalextension/issues/534
 */
class ConfigSaveSubscriber implements EventSubscriberInterface {

  public function __construct(
    protected StateInterface $state,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    return [ConfigEvents::SAVE => 'onConfigSave'];
  }

  /**
   * Records config change detection results.
   */
  public function onConfigSave(ConfigCrudEvent $event): void {
    $config = $event->getConfig();
    if ($config->getName() !== 'system.site') {
      return;
    }

    $log = $this->state->get('behat_test.config_save_log', []);
    $log[] = [
      'name' => $config->getName(),
      'changed' => $event->isChanged('name'),
      'original' => $config->getOriginal('name'),
    ];
    $this->state->set('behat_test.config_save_log', $log);
  }

}
