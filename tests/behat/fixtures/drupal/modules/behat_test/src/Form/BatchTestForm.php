<?php

declare(strict_types=1);

namespace Drupal\behat_test\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * A form that triggers a batch operation for testing batch wait steps.
 */
class BatchTestForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'behat_test_batch_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $form['run_batch'] = [
      '#type' => 'submit',
      '#value' => $this->t('Run batch'),
    ];

    // Attach jQuery so iWaitForTheBatchJobToFinish works on redirect back.
    $form['#attached']['library'][] = 'core/jquery';

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $batch = [
      'title' => $this->t('Processing batch test'),
      'operations' => [
        [[static::class, 'batchProcess'], [5]],
      ],
      'finished' => [static::class, 'batchFinished'],
    ];
    batch_set($batch);
  }

  /**
   * Batch operation callback.
   */
  public static function batchProcess(int $total, array &$context): void {
    if (!isset($context['sandbox']['progress'])) {
      $context['sandbox']['progress'] = 0;
      $context['sandbox']['max'] = $total;
    }

    // Sleep briefly so the batch progress bar is visible to the browser.
    usleep(500000);

    $context['sandbox']['progress']++;
    $context['finished'] = $context['sandbox']['progress'] / $context['sandbox']['max'];
    $context['message'] = t('Processing @current of @total', [
      '@current' => $context['sandbox']['progress'],
      '@total' => $context['sandbox']['max'],
    ]);
  }

  /**
   * Batch finished callback.
   */
  public static function batchFinished(bool $success, array $results, array $operations): void {
    if ($success) {
      \Drupal::messenger()->addStatus(t('Batch test completed successfully.'));
    }
  }

}
