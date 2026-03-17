<?php

/**
 * @file
 * Deploy hooks for the behat_test module.
 */

/**
 * Attach editorial workflow to the article content type.
 *
 * Computed base fields like 'moderation_state' are only available when a
 * workflow is attached to the content type. The Standard profile ships the
 * editorial workflow as optional config, but it is not imported when
 * content_moderation is installed as a behat_test dependency. This deploy
 * hook runs after module install and creates the workflow if needed.
 *
 * @see https://github.com/jhedstrom/drupalextension/issues/787
 */
function behat_test_deploy_add_editorial_workflow(): string {
  $workflow = \Drupal\workflows\Entity\Workflow::load('editorial');

  if (!$workflow) {
    $workflow = \Drupal\workflows\Entity\Workflow::create([
      'id' => 'editorial',
      'label' => 'Editorial',
      'type' => 'content_moderation',
    ]);
    $type_plugin = $workflow->getTypePlugin();
    $type_plugin->addState('draft', ['label' => 'Draft', 'published' => FALSE, 'default_revision' => FALSE, 'weight' => -5]);
    $type_plugin->addState('published', ['label' => 'Published', 'published' => TRUE, 'default_revision' => TRUE, 'weight' => 0]);
    $type_plugin->addTransition('create_new_draft', 'Create New Draft', ['draft', 'published'], 'draft');
    $type_plugin->addTransition('publish', 'Publish', ['draft', 'published'], 'published');
  }

  $workflow->getTypePlugin()->addEntityTypeAndBundle('node', 'article');
  $workflow->save();

  return 'Attached editorial workflow to article content type.';
}
