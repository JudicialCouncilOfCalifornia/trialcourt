<?php

namespace Drupal\jcc_pdf_upload_validation_checker\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\user\RoleInterface;

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

    $form['equal_web_settings']['equal_web_api_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('EqualWeb API key'),
      '#default_value' => $config->get('equal_web_api_key'),
      '#required' => TRUE,
    ];

    $form['pdf_validation_bypass'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable manual pdf validation bypass'),
      '#default_value' => $config->get('pdf_validation_bypass') ?? FALSE,
    ];

    $role_options = [];
    $roles = \Drupal::entityTypeManager()->getStorage('user_role')->loadMultiple();
    foreach ($roles as $role) {
      if ($role instanceof RoleInterface) {
        $role_options[$role->id()] = $role->label();
      }
    }

    $configured_roles = $config->get('bypass_allowed_roles');
    if (!is_array($configured_roles)) {
      $configured_roles = ['editor', 'administrator', 'manager'];
    }

    $default_roles = array_values(array_intersect($configured_roles, array_keys($role_options)));

    $form['bypass_allowed_roles'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Roles allowed to bypass'),
      '#options' => $role_options,
      '#default_value' => $default_roles,
      '#states' => [
        'visible' => [
          ':input[name="pdf_validation_bypass"]' => ['checked' => TRUE],
        ],
      ],
      '#description' => $this->t('Only selected roles will see the bypass validation checkbox on media upload forms.'),
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

    if ((bool) $form_state->getValue('pdf_validation_bypass')) {
      $selected_roles = array_filter($form_state->getValue('bypass_allowed_roles') ?? []);
      if (!$selected_roles) {
        $form_state->setErrorByName('bypass_allowed_roles', $this->t('Select at least one role allowed to bypass validation.'));
      }
    }

    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $selected_roles = array_values(array_filter($form_state->getValue('bypass_allowed_roles') ?? []));
    $bypass_enabled = (bool) $form_state->getValue('pdf_validation_bypass');

    $this->config('jcc_pdf_upload_validation_checker.settings')
      ->set('pdf_validation_api', $form_state->getValue('pdf_validation_api'))
      ->set('equal_web_api_key', $form_state->getValue('equal_web_api_key'))
      ->set('pdf_validation_bypass', $bypass_enabled)
      ->set('bypass_allowed_roles', $selected_roles)
      ->save();

    parent::submitForm($form, $form_state);
  }

}
