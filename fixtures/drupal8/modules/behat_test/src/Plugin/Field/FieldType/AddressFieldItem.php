<?php

/**
 * @file
 * Contains \Drupal\behat_test\Plugin\Field\FieldType\AddressFieldItem.
 */

namespace Drupal\behat_test\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\TypedData\DataDefinition;

/**
 * Plugin implementation of a simulated 'address_field' field type.
 *
 * This is intended as a temporary solution for testing complex fields using
 * the Behat Extension. Once the AddressField module is ported this will become
 * obsolete.
 *
 * @FieldType(
 *   id = "behat_test_address_field",
 *   label = @Translation("Address"),
 *   module = "behat_test",
 *   description = @Translation("A simulated address field type, intended for testing the Behat Extension."),
 *   default_widget = "behat_test_address_field_default",
 *   default_formatter = "behat_test_address_field"
 * )
 */
class AddressFieldItem extends FieldItemBase {

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    return [
      'country' => DataDefinition::create('string')->setLabel(t('Country')),
      'locality' => DataDefinition::create('string')->setLabel(t('Locality')),
      'thoroughfare' => DataDefinition::create('string')->setLabel(t('Thoroughfare')),
      'postal_code' => DataDefinition::create('string')->setLabel(t('Postal code')),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    return [
      'columns' => [
        'country' => [
          'description' => 'The ISO country code.',
          'type' => 'varchar',
          'length' => 2,
        ],
        'locality' => [
          'description' => 'The locality.',
          'type' => 'varchar',
          'length' => 255,
        ],
        'thoroughfare' => [
          'description' => 'The thoroughfare.',
          'type' => 'varchar',
          'length' => 255,
        ],
        'postal_code' => [
          'description' => 'The postal code.',
          'type' => 'varchar',
          'length' => 255,
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    $empty = TRUE;
    foreach (['country', 'locality', 'thoroughfare', 'postal_code'] as $column) {
      $empty &= $this->get($column)->getValue() === NULL;
    }
    return (bool) $empty;
  }

}
