<?php

namespace Drupal\xlsx\Plugin\xlsx\export;

use Drupal\xlsx\Plugin\XlsxExportBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Export as CSV.
 *
 * @XlsxExport(
 *   id = "csv",
 *   name = @Translation("CSV"),
 *   description = @Translation("Export as CSV file."),
 *   data_type = "Csv",
 *   source_types = {
 *     "csv_file",
 *     "csv_file_path",
 *     "csv_file_url",
 *     "xlsx_file",
 *     "google_sheets",
 *   }
 * )
 */
class CsvFile extends XlsxExportBase {

  /**
   * {@inheritdoc}
   */
  public function exportForm(array $form, FormStateInterface $form_state, $xlsx = NULL) {
    $form_state->setStorage(['xlsx' => $xlsx]);
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitExportForm(array &$form, FormStateInterface $form_state) {
    $storage = $form_state->getStorage();
    $params = [
      'password_protected' => $form_state->getValue('password_protected'),
      'password' => $form_state->getValue('password'),
    ];
    $this->runExport($storage['xlsx'], $form_state->getValue('format'), $form_state->getValue('remote'), $params);
  }

}
