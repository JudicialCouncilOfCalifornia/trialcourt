<?php

namespace Drupal\jcc_dynamic_sort\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form to import media as document content type.
 *
 * @internal
 */
class DynamicSortForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'jcc_dynamic_sort_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Add a welcome message.
    \Drupal::messenger()->addMessage($this->t('Welcome to the designing form of the Dynamic Sort module.'));

    $form['welcome'] = [
      '#markup' => $this->t('<p><strong>Welcome to the designing form of the Dynamic Sort module.</strong></p>'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Placeholder for submission logic.
  }
}
