<?php

namespace Drupal\xlsx\Form;

use Drupal\xlsx\Form\Mapping\BaseForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Import XLSX form controller.
 *
 * @ingroup xlsx
 */
class ImportXlsxForm extends BaseForm {

  /**
   * {@inheritdoc}.
   */
  public function getFormId() {
    return 'xlsx_import_form';
  }

  /**
   * Title callback.
   */
  public function getTitle() {
    $xlsx = \Drupal::routeMatch()->getParameter('xlsx');
    return $this->t('Import %name', ['%name' => $xlsx->label()]);
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $xlsx = NULL) {
    $form = parent::buildForm($form, $form_state);
    $form_state->setStorage(['xlsx' => $xlsx]);
    $xlsx_source = $this->xlsxSourceManager->createInstance($xlsx->getSourcePlugin());
    $data = $xlsx->getMapping();
    $import_form = $xlsx_source->importForm($form, $form_state);
    if ($import_form !== $form) {
      $form = array_merge($form, $import_form);
      if (!empty($data['source_name'])) {
        $form['source_name'] = [
          '#type' => 'item',
          '#title' => $this->t('File used for mapping'),
          '#markup' => $data['source_name'],
        ];
      }
      $form['actions']['submit']['#value'] = $this->t('Upload & Run Import');
    }
    else {
      $form['missing-plugin']['#markup'] = $this->t('Plugin %name does not have import form.', ['%name' => $xlsx_source->getName()]);
      unset($form['actions']['submit']);
    }
    $form['actions']['cancel']['#attributes']['class'] = ['button', 'dialog-cancel'];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $storage = $form_state->getStorage();
    $xlsx_source = $this->xlsxSourceManager->createInstance($storage['xlsx']->getSourcePlugin());
    if ($batch_items = $xlsx_source->submitImportForm($form, $form_state, $storage['xlsx'])) {
      $batch = [
        'title' => $this->t('Import %name', ['%name' => $storage['xlsx']->label()]),
        'finished' => '\Drupal\xlsx\XlsxBatchOps::completeImportCallback',
        'operations' => [],
      ];
      // Purge data
      if ($entity_ids = $this->loadEntitiesByMapping($storage['xlsx']->id())) {
        foreach (array_chunk($entity_ids, 100) as $ids) {
          $batch['operations'][] = ['\Drupal\xlsx\XlsxBatchOps::purge', [$ids]];
        }
      }

      foreach ($batch_items as $item) {
        $batch['operations'][] = $item;
      }
      $storage['xlsx']->setLastImport();
      batch_set($batch);
    }
  }

}
