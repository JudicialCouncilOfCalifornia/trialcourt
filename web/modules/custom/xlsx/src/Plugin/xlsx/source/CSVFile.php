<?php

namespace Drupal\xlsx\Plugin\xlsx\source;

use Drupal\xlsx\Plugin\XlsxSourceBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * CSV file source plugin.
 *
 * @XlsxSource(
 *   id = "csv_file",
 *   name = @Translation("CSV"),
 *   description = @Translation("Use local file to create mapping for import and export as downloadable file.")
 * )
 */
class CsvFile extends XlsxSourceBase {

  /**
   * {@inheritdoc}
   */
  public function sourceForm(array $form, FormStateInterface $form_state) {
    $form['csv'] = [
      '#type' => (\Drupal::moduleHandler()->moduleExists('external_media')) ? 'external_media' : 'managed_file',
      '#title' => $this->t('CSV file template'),
      '#upload_location' => 'public://spreadsheets',
      '#upload_validators' => [
        'file_validate_extensions' => ['csv'],
      ],
      '#description' => $this->t('This file is only used to map fields in Drupal with appropriate columns in the CSV file. No data import happening during this process.'),
      '#required' => TRUE,
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
    $csv_file = $form_state->getValue('csv', 0);
    if (isset($csv_file[0]) && !empty($csv_file[0])) {
      $params = [
        'delimiter' => $form_state->getValue('delimiter', ','),
        'enclosure' => $form_state->getValue('enclosure', '"'),
        'escape' => $form_state->getValue('escape', '\\'),
        'encoding' => $form_state->getValue('encoding', 'UTF-8'),
      ];
      $this->store->set('csv', $params);
      $this->store->set('sheet_columns', $this->getColumnsFromFile($csv_file[0], 'Csv'));
      return TRUE;
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function importForm(array $form, FormStateInterface $form_state) {
    $form = $this->sourceForm($form, $form_state);
    unset($form['delimiter'], $form['enclosure'], $form['escape'], $form['encoding']);
    $form['csv']['#description'] = $this->t('Running a new import will remove preiously imported data and replace with the latest.');
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitImportForm(array &$form, FormStateInterface $form_state, $xlsx = NULL) {
    $csv_file = $form_state->getValue('csv', 0);
    if (isset($csv_file[0]) && !empty($csv_file[0])) {
      return $this->buildBatchItems($xlsx, $csv_file[0], 'Csv');
    }
    return FALSE;
  }

}
