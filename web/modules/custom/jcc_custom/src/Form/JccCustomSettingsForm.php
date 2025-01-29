<?php

namespace Drupal\jcc_custom\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Defines a form to configure the "Enable new listing page" setting.
 */
class jccCustomSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['jcc_custom.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'jcc_custom_settings_form';
  }

  /**
   * Builds the form.
   *
   * @param array $form
   *   The form structure.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   *
   * @return array
   *   The form structure.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Get the current configuration value for enable_new_listing_page.
    $config = $this->config('jcc_custom.settings');

    // Add a checkbox field to enable or disable the new listing page.
    $form['enable_new_listing_page'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable new listing page'),
      '#default_value' => $config->get('enable_new_listing_page'),
      '#description' => $this->t('Check this box to enable the new listing page.'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Save the value of the checkbox to the configuration.
    $this->config('jcc_custom.settings')
      ->set('enable_new_listing_page', $form_state->getValue('enable_new_listing_page'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
