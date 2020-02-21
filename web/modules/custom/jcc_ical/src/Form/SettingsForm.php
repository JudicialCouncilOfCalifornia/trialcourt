<?php

namespace Drupal\jcc_ical\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure jcc_ical settings for this site.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'jcc_ical_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['jcc_ical.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Create checkboxes for all available content types.
    $types = \Drupal::entityTypeManager()
      ->getStorage('node_type')
      ->loadMultiple();

    foreach ($types as $name => $type) {
      $options[$name] = $name;
    }

    $form['types'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Select Content Types to provide iCal support for.'),
      '#options' => $options,
      '#default_value' => $this->config('jcc_ical.settings')->get('types'),
      '#description' => $this->t('Selected content types must have a Date Range field "field_date_range".')
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // Nothing to validate.
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('jcc_ical.settings')
      ->set('types', $form_state->getValue('types'))
      ->save();
    parent::submitForm($form, $form_state);
  }

}
