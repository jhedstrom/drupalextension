<?php

/**
 * @file
 * Deploy hooks for the behat_test module.
 */

declare(strict_types=1);

use Drupal\Core\Datetime\Entity\DateFormat;
use Drupal\workflows\Entity\Workflow;

/**
 * Attach editorial workflow to the article content type.
 *
 * Computed base fields like 'moderation_state' are only available when a
 * workflow is attached to the content type. The Standard profile ships the
 * editorial workflow as optional config, but it is not imported when
 * content_moderation is installed as a behat_test dependency. This deploy
 * hook runs after module install and creates the workflow if needed.
 */
function behat_test_deploy_add_editorial_workflow(): string {
  $workflow = Workflow::load('editorial');

  if (!$workflow) {
    $workflow = Workflow::create([
      'id' => 'editorial',
      'label' => 'Editorial',
      'type' => 'content_moderation',
    ]);
    $type_plugin = $workflow->getTypePlugin();
    $type_plugin->addState('draft', [
      'label' => 'Draft',
      'published' => FALSE,
      'default_revision' => FALSE,
      'weight' => -5,
    ]);
    $type_plugin->addState('published', [
      'label' => 'Published',
      'published' => TRUE,
      'default_revision' => TRUE,
      'weight' => 0,
    ]);
    $type_plugin->addTransition('create_new_draft', 'Create New Draft', ['draft', 'published'], 'draft');
    $type_plugin->addTransition('publish', 'Publish', ['draft', 'published'], 'published');
  }

  $type_plugin = $workflow->getTypePlugin();
  $type_plugin->addEntityTypeAndBundle('node', 'article');

  // Set default moderation state to 'published' so existing tests that create
  // article nodes without specifying moderation_state continue to work.
  // Read settings from the plugin (not the entity) to preserve the
  // addEntityTypeAndBundle() change made above.
  $configuration = $type_plugin->getConfiguration();
  $configuration['default_moderation_state'] = 'published';
  $workflow->set('type_settings', $configuration);

  $workflow->save();

  return 'Attached editorial workflow to article content type.';
}

/**
 * Uninstall page_cache and big_pipe to simplify test assertions.
 *
 * The Standard profile enables these caching modules by default. They
 * interfere with Behat tests that assert exact page output, so we
 * uninstall them during provisioning. Scenarios that exercise the
 * BigPipe NOJS bypass (the '@bigpipe' tag triggered by 'BigPipeTrait')
 * install 'big_pipe' explicitly with the 'I install a :module module'
 * step.
 */
function behat_test_deploy_uninstall_caching_modules(): string {
  $module_installer = \Drupal::service('module_installer');
  $module_installer->uninstall(['page_cache', 'big_pipe']);

  return 'Uninstalled page_cache and big_pipe.';
}

/**
 * Set the Olivero medium date format to match test expectations.
 */
function behat_test_deploy_set_date_format(): string {
  $date_format = DateFormat::load('olivero_medium');

  if ($date_format) {
    $date_format->setPattern('j F, Y');
    $date_format->save();
  }

  return 'Set olivero_medium date format pattern.';
}

/**
 * Disable automated cron to prevent test interference.
 */
function behat_test_deploy_disable_automated_cron(): string {
  \Drupal::configFactory()
    ->getEditable('automated_cron.settings')
    ->set('interval', 0)
    ->save();

  return 'Disabled automated cron.';
}

/**
 * Enable visitor registration to allow user registration tests.
 */
function behat_test_deploy_enable_visitor_registration(): string {
  \Drupal::configFactory()
    ->getEditable('user.settings')
    ->set('register', 'visitors')
    ->save();

  return 'Enabled visitor registration.';
}
