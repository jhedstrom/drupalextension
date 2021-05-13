<?php

namespace Drupal\behat_test\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'behat_test_address_field' widget.
 *
 * @FieldWidget(
 *   id = "behat_test_address_field_default",
 *   label = @Translation("Address field"),
 *   module = "behat_test",
 *   field_types = {
 *     "behat_test_address_field"
 *   }
 * )
 */
class AddressFieldWidget extends WidgetBase {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    // Set up the form element.
    $element += ['#type' => 'details', '#open' => TRUE];

    // Add in the textfields.
    $columns = [
      'country' => t('Country'),
      'locality' => t('Locality'),
      'thoroughfare' => t('Thoroughfare'),
      'postal_code' => t('Postal code'),
    ];
    foreach ($columns as $key => $title) {
      $element[$key] = [
        '#type' => 'textfield',
        '#title' => $title,
        '#default_value' => isset($items[$delta]->$key) ? $items[$delta]->$key : '',
      ];
    }

    return $element;
  }

}
