<?php

namespace Drupal\xlsx\Plugin\xlsx\source;

use Drupal\xlsx\Plugin\XlsxSourceBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * XLS file source plugin.
 *
 * @XlsxSource(
 *   id = "xls_file",
 *   name = @Translation("XLS"),
 *   description = @Translation("Use local file to create mapping for import and export as downloadable file.")
 * )
 */
class XlsFile extends XlsxSourceBase {

  /**
   * {@inheritdoc}
   */
  public function sourceForm(array $form, FormStateInterface $form_state) {
    $form['xls'] = [
      '#type' => (\Drupal::moduleHandler()->moduleExists('external_media')) ? 'external_media' : 'managed_file',
      '#title' => $this->t('XLS file template'),
      '#upload_location' => 'public://spreadsheets',
      '#upload_validators' => [
        'file_validate_extensions' => ['xls'],
      ],
      '#description' => $this->t('This file is only used to map fields in Drupal with appropriate columns in the XLS file. No data import happening during this process.'),
      '#required' => TRUE,
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitSourceForm(array &$form, FormStateInterface $form_state) {
    $xlsx_file = $form_state->getValue('xls', 0);
    if (isset($xlsx_file[0]) && !empty($xlsx_file[0])) {
      $this->store->set('sheet_columns', $this->getColumnsFromFile($xlsx_file[0], 'Xls'));
      return TRUE;
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function importForm(array $form, FormStateInterface $form_state) {
    $form = $this->sourceForm($form, $form_state);
    $form['xls']['#description'] = $this->t('Running a new import will remove preiously imported data and replace with the latest.');
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitImportForm(array &$form, FormStateInterface $form_state, $xlsx = NULL) {
    $xlsx_file = $form_state->getValue('xls', 0);
    if (isset($xlsx_file[0]) && !empty($xlsx_file[0])) {
      return $this->buildBatchItems($xlsx, $xlsx_file[0], 'Xls');
    }
    return FALSE;
  }

}
