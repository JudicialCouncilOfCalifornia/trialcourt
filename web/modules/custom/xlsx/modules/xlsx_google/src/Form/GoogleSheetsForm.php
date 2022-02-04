<?php

namespace Drupal\xlsx_google\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\file\Entity\File;

/**
 * Class GoogleSheetsForm.
 *
 * @ingroup xlsx
 */
class GoogleSheetsForm extends ConfigFormBase {

  const CONFIG_NAME = 'xlsx.google_sheets';

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [static::CONFIG_NAME];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'xlsx_google_sheets_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    if (class_exists('\Google_Client') && class_exists('\Google_Service_Sheets')) {
      $config = $this->config(static::CONFIG_NAME);
      $form['credentials_files'] = [
        '#title' => $this->t('Credentials JSON file'),
        '#type' => 'managed_file',
        '#upload_validators' => [
          'file_validate_extensions' => ['json'],
        ],
        '#default_value' => ($file_id = \Drupal::state()->get('xlsx_config_file_id')) ? [$file_id] : [],
        '#upload_location' => 'public://spreadsheets',
        '#description' => $this->t('Download credentials JSON file from Google Console.'),
        '#required' => TRUE,
      ];
      $form['service_credentials_files'] = [
        '#title' => $this->t('Service Account Credentials JSON file'),
        '#type' => 'managed_file',
        '#upload_validators' => [
          'file_validate_extensions' => ['json'],
        ],
        '#default_value' => ($file_id = \Drupal::state()->get('xlsx_service_config_file_id')) ? [$file_id] : [],
        '#upload_location' => 'public://spreadsheets',
        '#description' => $this->t('Download Service Account credentials JSON file from Google Console.'),
        '#required' => TRUE,
      ];
      $form['info'] = [
        '#type'  => 'fieldset',
        '#title' => $this->t('Configuration instructions'),
      ];
      $form['info']['info'] = [
        '#markup' => '<p>To get started using Google Picker API, you need to first '
        . '<a href="https://console.developers.google.com/flows/enableapi?apiid=picker" target="_blank">'
        . 'create or select a project in the Google Developers Console and enable the API</a>. Make sure you create Service Account and download JSON file with credentials.</p>'
        . '<ul><li>Enable <strong>Google Picker API</strong> <em>(required)</em></li>'
        . '<li>Enable <strong>Google Drive API</strong> <em>(required)</em></li>'
      . '<li>Enable <strong>Google Sheets API</strong> <em>(required)</em></li></ul>',
      ];
    }
    else {
      $form['info']['#markup'] = $this->t('Google_Client and Google_Service_Sheets PHP classes are missing.');
    }
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $credentials_files = $form_state->getValue('credentials_files', 0);
    if (isset($credentials_files[0]) && !empty($credentials_files[0])) {
      $file = File::load($credentials_files[0]);
      $file->setPermanent();
      $file->save();
      $content = file_get_contents($file->getFileUri());
      $json = json_decode($content, TRUE);
      $client_id = !empty($json['web']['client_id']) ? $json['web']['client_id'] : '';
      $e = explode('-', $client_id);
      $this->config(static::CONFIG_NAME)
        ->set('client_id', $client_id)
        ->set('app_id', $e[0])
        ->save();
      \Drupal::state()->set('xlsx_config_file_id', $credentials_files[0]);
    }
    $service_credentials_files = $form_state->getValue('service_credentials_files', 0);
    if (isset($service_credentials_files[0]) && !empty($service_credentials_files[0])) {
      $file = File::load($service_credentials_files[0]);
      $file->setPermanent();
      $file->save();
      \Drupal::state()->set('xlsx_service_config_file_id', $service_credentials_files[0]);
    }
    parent::submitForm($form, $form_state);
  }

}
