<?php

namespace Drupal\jcc_pdf_upload_validation_checker\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure JCC PDF upload validation checker settings for this site.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'jcc_pdf_upload_validation_checker_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['jcc_pdf_upload_validation_checker.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('jcc_pdf_upload_validation_checker.settings');

    $form['pdf_validation_api'] = [
      '#type' => 'select',
      '#title' => $this->t('Api to use for validation'),
      '#options' => [
        'PDF audit' => $this->t('pdf_audit'),
        'EqualWeb' => $this->t('equal_web'),
      ],
      '#default_value' => $config->get('pdf_validation_api') ?? 'pdf_audit',
      '#required' => TRUE,
    ];

    $form['equal_web_settings'] = [
      '#type' => 'details',
      '#title' => $this->t('EqualWeb Settings'),
      '#open' => TRUE,
      '#states' => [
        'visible' => [
          ':input[name="pdf_validation_api"]' => ['value' => 'EqualWeb'],
        ],
      ],
    ];

    $form['equal_web_settings']['api_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('EqualWeb API key'),
      '#default_value' => $config->get('api_key'),
      '#required' => TRUE,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    if ($form_state->getValue('pdf_validation_api') != 'PDF audit' && $form_state->getValue('pdf_validation_api') != 'EqualWeb') {
      $form_state->setErrorByName('pdf_validation_api', $this->t('The value is not correct.'));
    }
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('jcc_pdf_upload_validation_checker.settings')
      ->set('pdf_validation_api', $form_state->getValue('pdf_validation_api'))
      ->set('api_key', $form_state->getValue('api_key'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
