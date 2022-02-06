<?php

namespace Drupal\xlsx_google\Plugin\xlsx\source;

use Google_Client;
use Google_Service_Sheets;
use Drupal\Core\Form\FormStateInterface;
use Drupal\xlsx\Plugin\XlsxSourceBase;
use Drupal\xlsx_google\Form\GoogleSheetsForm;

/**
 * Google Sheets file.
 *
 * @XlsxSource(
 *   id = "google_sheets",
 *   name = @Translation("Google Sheets"),
 *   description = @Translation("Use Google Sheets to create mapping for import and export.")
 * )
 */
class GoogleSheets extends XlsxSourceBase {

  protected $destination = 'public://spreadsheets';

  /**
   * {@inheritdoc}
   */
  public function sourceForm(array $form, FormStateInterface $form_state) {
    $config = \Drupal::config(GoogleSheetsForm::CONFIG_NAME);
    $form['remote_file'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Remote file'),
      '#attached' => [
        'library' => ['xlsx_google/xlsx.googlepicker'],
        'drupalSettings' => [
          'google_client_id' => $config->get('client_id'),
          'google_app_id' => $config->get('app_id'),
        ],
      ],
      '#prefix' => '<div class="google-picker-wrapper">
        <div class="google-picker-button">
          <a href="#" class="button" data-max-filesize="' . $this->getMaximumFileUploadSize() . '">' . $this->t('Pick a Spreadsheet...') . '</a>
        </div>
        <div class="hidden">',
      '#suffix' => '</div></div>',
      '#maxlength' => 1048,
      '#required' => TRUE,
    ];
    $form['picked_file'] = [
      '#markup' => '<br/><div id="google-picked-file"></div>
        <br/><hr>',
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitSourceForm(array &$form, FormStateInterface $form_state) {
    $file = $form_state->getValue('remote_file');
    list($id, $orignal_name, $google_token) = explode('@@@', $file);
    if ($file = $this->downloadGoogleSpreadsheet($file)) {
      $this->store->set('source_file_id', $id);
      $this->store->set('sheet_columns', $this->getColumnsFromFile($file->id(), 'Xlsx'));
      return TRUE;
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function importForm(array $form, FormStateInterface $form_state) {
    $form = $this->sourceForm($form, $form_state);
    $form['remote_file']['#description'] = $this->t('Running a new import will remove preiously imported data and replace with the latest.');
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitImportForm(array &$form, FormStateInterface $form_state, $xlsx = NULL) {
    $file = $form_state->getValue('remote_file');
    if ($file = $this->downloadGoogleSpreadsheet($file)) {
      return $this->buildBatchItems($xlsx, $file->id(), 'Xlsx');
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function classExists() {
    return class_exists('\Google_Client') && class_exists('\Google_Service_Sheets');
  }

  protected function getMaximumFileUploadSize() {  
    return min($this->convertPHPSizeToBytes(ini_get('post_max_size')), $this->convertPHPSizeToBytes(ini_get('upload_max_filesize')));  
  }

  /**
   * Download Google Sheets file.
   */
  protected function downloadGoogleSpreadsheet($file) {
    list($id, $orignal_name, $google_token) = explode('@@@', $file);
    $options = [
      'headers' => [
        'Authorization' => 'Bearer ' . $google_token,
      ],
    ];
    $client = \Drupal::httpClient();
    $response = $client->request('GET', 'https://docs.google.com/spreadsheets/d/' . $id . '/export?format=xlsx', $options);
    if ($response->getStatusCode() == 200) {
      $file = file_save_data($response->getBody()->getContents(), $this->destination . '/' . $orignal_name . '.xlsx');
      $file->setMimeType('application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
      $file->setTemporary();
      $file->save();
      return $file;
    }
    return FALSE;
  }

  /**
  * This function transforms the php.ini notation for numbers (like '2M') to an integer (2*1024*1024 in this case)
  * 
  * @param string $sSize
  * @return integer The value in bytes
  */
  protected function convertPHPSizeToBytes($sSize) {
    $sSuffix = strtoupper(substr($sSize, -1));
    if (!in_array($sSuffix, array('P', 'T', 'G', 'M', 'K'))) {
      return (int) $sSize;
    }
    $iValue = substr($sSize, 0, -1);
    switch ($sSuffix) {
      case 'P':
        $iValue *= 1024;
        // Fallthrough intended
      case 'T':
        $iValue *= 1024;
        // Fallthrough intended
      case 'G':
        $iValue *= 1024;
        // Fallthrough intended
      case 'M':
        $iValue *= 1024;
        // Fallthrough intended
      case 'K':
        $iValue *= 1024;
        break;
    }
    return (int) $iValue;
  }

}
