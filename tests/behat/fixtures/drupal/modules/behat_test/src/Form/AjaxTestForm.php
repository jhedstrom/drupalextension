<?php

declare(strict_types=1);

namespace Drupal\behat_test\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * A simple form with an AJAX callback for testing AJAX wait steps.
 */
class AjaxTestForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'behat_test_ajax_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $form['name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Name'),
      '#required' => FALSE,
    ];

    $form['agree'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('I agree'),
    ];

    $form['color'] = [
      '#type' => 'radios',
      '#title' => $this->t('Color'),
      '#options' => [
        'red' => $this->t('Red'),
        'blue' => $this->t('Blue'),
      ],
    ];

    $form['advanced'] = [
      '#type' => 'details',
      '#title' => $this->t('Advanced settings'),
      '#open' => FALSE,
    ];

    $form['advanced']['extra'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Extra'),
    ];

    $form['ajax_button'] = [
      '#type' => 'button',
      '#value' => $this->t('Load greeting'),
      '#ajax' => [
        'callback' => '::ajaxCallback',
        'wrapper' => 'ajax-result',
      ],
    ];

    $form['result'] = [
      '#type' => 'container',
      '#attributes' => ['id' => 'ajax-result'],
    ];

    if ($form_state->getValue('name')) {
      $form['result']['message'] = [
        '#markup' => '<p>Hello, ' . $form_state->getValue('name') . '!</p>',
      ];
    }

    return $form;
  }

  /**
   * AJAX callback that returns the result container.
   */
  public function ajaxCallback(array &$form, FormStateInterface $form_state): array {
    return $form['result'];
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    // No submit needed - AJAX handles it.
  }

}
