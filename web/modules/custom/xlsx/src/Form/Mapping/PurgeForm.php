<?php

namespace Drupal\xlsx\Form\Mapping;

use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Purge Data form controller.
 *
 * @ingroup xlsx
 */
class PurgeForm extends ConfirmFormBase {

  protected $xlsx;

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $xlsx = NULL) {
    $this->xlsx = $xlsx;
    $form = parent::buildForm($form, $form_state);
    $form['actions']['cancel']['#attributes']['class'] = ['button', 'dialog-cancel'];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $batch = [
      'title' => $this->t('Purging %name data', ['%name' => $this->xlsx->label()]),
      'finished' => '\Drupal\xlsx\XlsxBatchOps::completePurgeCallback',
      'operations' => [],
    ];
    if ($entity_ids = $this->loadEntitiesByMapping($this->xlsx->id())) {
      $storage = \Drupal::entityTypeManager()->getStorage('xlsx_data');
      foreach (array_chunk($entity_ids, 100) as $ids) {
        $batch['operations'][] = ['\Drupal\xlsx\XlsxBatchOps::purge', [$ids, $storage]];
      }
    }
    batch_set($batch);
    $message = t('Imported %name data purged.', ['%name' => $this->xlsx->label()]);
    $this->messenger()->addStatus($message);
    $this->getLogger('xlsx')->notice($message);
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() : string {
    return 'xlsx_purge_data_form';
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('xlsx.mapping');
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return t('Are you sure you would like to purge %name data?', ['%name' => $this->xlsx->label()]);
  }

  /**
   * Load entity by entity type and mapping ID.
   */
  protected function loadEntitiesByMapping($mapping_id) {
    $result = \Drupal::entityQuery('xlsx_data')->condition('mapping_id', $mapping_id)->execute();
    if ($ids = array_values($result)) {
      return $ids;
    }
  }

}
