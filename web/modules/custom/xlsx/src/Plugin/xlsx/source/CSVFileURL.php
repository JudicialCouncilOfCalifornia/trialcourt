<?php

namespace Drupal\xlsx\Plugin\xlsx\source;

use Drupal\xlsx\Plugin\XlsxSourceBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * CSV file URL source plugin.
 *
 * @XlsxSource(
 *   id = "csv_file_url",
 *   name = @Translation("CSV file URL"),
 *   description = @Translation("Use path to a file to create mapping for import and export as downloadable file."),
 *   cron = TRUE
 * )
 */
class CSVFileURL extends XlsxSourceBase {

  /**
   * {@inheritdoc}
   */
  public function sourceForm(array $form, FormStateInterface $form_state) {
    $form['csv'] = [
      '#type' => 'textfield',
      '#title' => $this->t('URL to a CSV file'),
      '#description' => $this->t('URL to a CSV file'),
      '#required' => TRUE,
    ];
    $form['cron'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Run this import on cron (Automatic imports)'),
    ];
    $options = [3600, 10800, 21600, 43200, 86400, 604800];
    $form['cron_frequency'] = [
      '#type' => 'select',
      '#title' => $this->t('Frequency'),
      '#options' => [0 => $this->t('On each cron run')] + array_map([\Drupal::service('date.formatter'), 'formatInterval'], array_combine($options, $options)),
      '#description' => $this->t('How often perform automatic imports (this process adds items to the Drupal Queue API). This configuration option also depends on how Cron is configured in the system.'),
      '#states' => [
        'visible' => [':input[name="cron"]' => ['checked' => TRUE]]
      ],
    ];
    $form['delimiter'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Delimiter'),
      '#default_value' => ',',
      '#size' => 5,
      '#required' => TRUE,
    ];
    $form['enclosure'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Enclosure'),
      '#default_value' => '"',
      '#size' => 5,
      '#required' => TRUE,
    ];
    $form['escape'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Escape'),
      '#default_value' => '\\',
      '#size' => 5,
      '#required' => TRUE,
    ];
    $form['encoding'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Encoding'),
      '#default_value' => 'UTF-8',
      '#size' => 10,
      '#required' => TRUE,
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitSourceForm(array &$form, FormStateInterface $form_state) {
    $csv_file = system_retrieve_file($form_state->getValue('csv', ''));
    if (!empty($csv_file)) {
      $params = [
        'delimiter' => $form_state->getValue('delimiter', ','),
        'enclosure' => $form_state->getValue('enclosure', '"'),
        'escape'    => $form_state->getValue('escape', '\\'),
        'encoding'  => $form_state->getValue('encoding', 'UTF-8'),
      ];
      $this->store->set('csv', $params);
      $this->store->set('file_path', $form_state->getValue('csv'));
      $this->store->set('cron', (bool) $form_state->getValue('cron'));
      $this->store->set('cron_frequency', (int) $form_state->getValue('cron_frequency'));
      $this->store->set('sheet_columns', $this->getColumnsFromFilePath($csv_file, 'Csv'));
      return TRUE;
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function importForm(array $form, FormStateInterface $form_state) {
    $form['source_type'] = [
      '#title' => $this->t('Source type'),
      '#type' => 'radios',
      '#options' => [
        'url' => $this->t('URL (manual)'),
        'upload' => $this->t('Upload a file (manual)'),
      ],
      '#default_value' => 'path',
      '#description' => $this->t('This won\'t update the import source file. The purpose of this form is to manually perform import.'),
    ];
    $form = $this->sourceForm($form, $form_state);
    $storage = $form_state->getStorage();
    if (!empty($storage['xlsx'])) {
      $data = $storage['xlsx']->getMapping();
      if (!empty($data['file_path'])) {
        $form['csv']['#default_value'] = $data['file_path'];
      }
    }
    unset($form['delimiter'], $form['enclosure'], $form['escape'], $form['encoding'], $form['cron'], $form['cron_frequency']);
    $form['csv']['#description'] = $this->t('Running a new import will remove preiously imported data and replace with the latest.');
    $form['csv']['#states'] = [
      'visible' => [':input[name="source_type"]' => ['value' => 'url']]
    ];
    $form['csv_file_wrapper'] = [
      '#type' => 'fieldset',
      '#states' => [
        'visible' => [':input[name="source_type"]' => ['value' => 'upload']]
      ],
    ];
    $form['csv_file_wrapper']['csv_file'] = [
      '#type' => (\Drupal::moduleHandler()->moduleExists('external_media')) ? 'external_media' : 'managed_file',
      '#title' => $this->t('Upload CSV file'),
      '#upload_location' => 'public://spreadsheets',
      '#upload_validators' => [
        'file_validate_extensions' => ['csv'],
      ],
      '#description' => $this->t('<strong>WARNING:</strong> If you\'re not using original file the import could potentially break the site. Running a new import will remove preiously imported data and replace with the latest.'),
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitImportForm(array &$form, FormStateInterface $form_state, $xlsx = NULL) {
    $source_type = $form_state->getValue('source_type', 'url');
    if ($source_type == 'url') {
      $file_path = system_retrieve_file($form_state->getValue('csv', ''));
      if (!empty($file_path)) {
        return $this->buildBatchItemsFilePath($xlsx, $file_path, 'Csv');
      }
    }
    else {
      $csv_file = $form_state->getValue('csv_file', 0);
      if (isset($csv_file[0]) && !empty($csv_file[0])) {
        return $this->buildBatchItems($xlsx, $csv_file[0], 'Csv');
      }
    }
    return FALSE;
  }

}
