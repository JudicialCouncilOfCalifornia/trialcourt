<?php

namespace Drupal\jcc_pdf_upload_validation_checker\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\jcc_pdf_upload_validation_checker\Apis\Api\DefaultPdfAuditApi;
use Drupal\jcc_pdf_upload_validation_checker\Apis\Api\EqualWebPdfAuditApi;
use Drupal\jcc_pdf_upload_validation_checker\Apis\Api\PlanetariaPdfAuditApi;
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

    $form['enabled'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable PDF upload validation checker'),
      '#default_value' => $config->get('enabled') ?? FALSE,
      '#description' => $this->t('When disabled, all PDF upload validation checking/enforcement is skipped and the "PDF Validation" report link/page is hidden.'),
    ];

    $form['pdf_validation_api'] = [
      '#type' => 'select',
      '#title' => $this->t('Api to use for validation'),
      '#options' => [
        DefaultPdfAuditApi::ID => $this->t('PDF audit (default)'),
        EqualWebPdfAuditApi::ID => $this->t('EqualWeb'),
        PlanetariaPdfAuditApi::ID => $this->t('Planetaria'),
      ],
      '#default_value' => $config->get('pdf_validation_api') ?? DefaultPdfAuditApi::ID,
      '#required' => TRUE,
    ];

    $form['equal_web_settings'] = [
      '#type' => 'details',
      '#title' => $this->t('EqualWeb Settings'),
      '#open' => TRUE,
      '#states' => [
        'visible' => [
          ':input[name="pdf_validation_api"]' => ['value' => EqualWebPdfAuditApi::ID],
        ],
      ],
    ];

    $form['equal_web_settings']['equal_web_api_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('EqualWeb API key'),
      '#default_value' => $config->get('equal_web_api_key'),
    ];

    $form['planetaria_settings'] = [
      '#type' => 'details',
      '#title' => $this->t('Planetaria Settings'),
      '#open' => TRUE,
      '#states' => [
        'visible' => [
          ':input[name="pdf_validation_api"]' => ['value' => PlanetariaPdfAuditApi::ID],
        ],
      ],
    ];

    $form['planetaria_settings']['planetaria_api_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Planetaria API key'),
      '#default_value' => $config->get('planetaria_api_key'),
    ];

    $form['planetaria_settings']['planetaria_use_staging'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Use Planetaria staging environment'),
      '#default_value' => $config->get('planetaria_use_staging') ?? FALSE,
      '#description' => $this->t('Enable only when the API key above was generated from the Planetaria/Inquiros staging dashboard (staging.pdfchecker.inquiros.io). Staging keys are rejected by the production API with a 401 API_KEY_ENVIRONMENT error.'),
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
    $valid_apis = [DefaultPdfAuditApi::ID, EqualWebPdfAuditApi::ID, PlanetariaPdfAuditApi::ID];
    $selected_api = $form_state->getValue('pdf_validation_api');
    if (!in_array($selected_api, $valid_apis, TRUE)) {
      $form_state->setErrorByName('pdf_validation_api', $this->t('The value is not correct.'));
    }

    // Only the key for the currently selected API is required — the other
    // key field is hidden via #states, so it can't be made #required.
    if ($selected_api === EqualWebPdfAuditApi::ID && trim((string) $form_state->getValue('equal_web_api_key')) === '') {
      $form_state->setErrorByName('equal_web_api_key', $this->t('EqualWeb API key is required.'));
    }
    if ($selected_api === PlanetariaPdfAuditApi::ID && trim((string) $form_state->getValue('planetaria_api_key')) === '') {
      $form_state->setErrorByName('planetaria_api_key', $this->t('Planetaria API key is required.'));
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
      ->set('enabled', (bool) $form_state->getValue('enabled'))
      ->set('pdf_validation_api', $form_state->getValue('pdf_validation_api'))
      ->set('equal_web_api_key', $form_state->getValue('equal_web_api_key'))
      ->set('planetaria_api_key', $form_state->getValue('planetaria_api_key'))
      ->set('planetaria_use_staging', (bool) $form_state->getValue('planetaria_use_staging'))
      ->set('pdf_validation_bypass', $bypass_enabled)
      ->set('bypass_allowed_roles', $selected_roles)
      ->save();

    parent::submitForm($form, $form_state);
  }

}
