<?php

namespace Drupal\xlsx\Plugin\xlsx\source;

use Drupal\xlsx\Plugin\XlsxSourceBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * SLK file source plugin.
 *
 * @XlsxSource(
 *   id = "slk_file",
 *   name = @Translation("SLK"),
 *   description = @Translation("Use local file to create mapping for import and export as downloadable file.")
 * )
 */
class SlkFile extends XlsxSourceBase {

  /**
   * {@inheritdoc}
   */
  public function sourceForm(array $form, FormStateInterface $form_state) {
    $form['slk'] = [
      '#type' => (\Drupal::moduleHandler()->moduleExists('external_media')) ? 'external_media' : 'managed_file',
      '#title' => $this->t('SLK file template'),
      '#upload_location' => 'public://spreadsheets',
      '#upload_validators' => [
        'file_validate_extensions' => ['slk'],
      ],
      '#description' => $this->t('This file is only used to map fields in Drupal with appropriate columns in the SLK file. No data import happening during this process.'),
      '#required' => TRUE,
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitSourceForm(array &$form, FormStateInterface $form_state) {
    $slk_file = $form_state->getValue('slk', 0);
    if (isset($slk_file[0]) && !empty($slk_file[0])) {
      $this->store->set('sheet_columns', $this->getColumnsFromFile($slk_file[0], 'Slk'));
      return TRUE;
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function importForm(array $form, FormStateInterface $form_state) {
    $form = $this->sourceForm($form, $form_state);
    $form['slk']['#description'] = $this->t('Running a new import will remove preiously imported data and replace with the latest.');
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitImportForm(array &$form, FormStateInterface $form_state, $xlsx = NULL) {
    $slk_file = $form_state->getValue('slk', 0);
    if (isset($slk_file[0]) && !empty($slk_file[0])) {
      return $this->buildBatchItems($xlsx, $slk_file[0], 'Slk');
    }
    return FALSE;
  }

}
