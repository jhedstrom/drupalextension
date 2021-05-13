<?php

namespace Drupal\behat_test\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Locale\CountryManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'behat_test_address_field' formatter.
 *
 * @FieldFormatter(
 *   id = "behat_test_address_field",
 *   label = @Translation("Address field formatter"),
 *   module = "behat_test",
 *   field_types = {
 *     "behat_test_address_field"
 *   }
 * )
 */
class AddressFieldFormatter extends FormatterBase implements ContainerFactoryPluginInterface {

  /**
   * The country manager.
   *
   * @var \Drupal\Core\Locale\CountryManagerInterface
   */
  protected $countryManager;

  /**
   * Constructs an AddressFieldFormatter object.
   *
   * @param string $plugin_id
   *   The plugin_id for the formatter.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The definition of the field to which the formatter is associated.
   * @param array $settings
   *   The formatter settings.
   * @param string $label
   *   The formatter label display setting.
   * @param string $view_mode
   *   The view mode.
   * @param array $third_party_settings
   *   Any third party settings.
   * @param \Drupal\Core\Locale\CountryManagerInterface $country_manager
   *   The country manager.
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, $label, $view_mode, array $third_party_settings, CountryManagerInterface $country_manager) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings);

    $this->countryManager = $country_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['label'],
      $configuration['view_mode'],
      $configuration['third_party_settings'],
      $container->get('country_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $language) {
    $elements = [];

    foreach ($items as $delta => $item) {
      foreach (['country', 'locality', 'thoroughfare', 'postal_code'] as $key) {
        $value = $item->$key;
        // Replace the country code with the country name.
        if ($key === 'country') {
          $countries = $this->countryManager->getList();
          $value = !empty($countries[$value]) ? $countries[$value] : '';
        }

        $elements[$delta][$key] = [
          '#type' => 'html_tag',
          '#tag' => 'p',
          '#value' => $value,
        ];
      }
    }

    return $elements;
  }

}
