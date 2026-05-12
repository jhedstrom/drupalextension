<?php

declare(strict_types=1);

namespace Drupal\behat_test\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Defines a minimal content entity used by the generic entity step tests.
 *
 * Exists so the 'Given :type entities:' step can be exercised against an
 * entity type that is neither node, user, nor taxonomy_term.
 *
 * @ContentEntityType(
 *   id = "behat_test_thing",
 *   label = @Translation("Behat test thing"),
 *   base_table = "behat_test_thing",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "label" = "title",
 *   },
 * )
 */
final class BehatTestThing extends ContentEntityBase {

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['title'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Title'))
      ->setRequired(TRUE);

    $fields['status'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Status'))
      ->setDefaultValue(TRUE);

    return $fields;
  }

}
