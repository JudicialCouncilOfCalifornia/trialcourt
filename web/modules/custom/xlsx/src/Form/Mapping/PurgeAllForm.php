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
class PurgeAllForm extends ConfirmFormBase {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);
    $form['actions']['cancel']['#attributes']['class'] = ['button', 'dialog-cancel'];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $batch = [
      'title' => $this->t('Purging all previously imported data'),
      'finished' => '\Drupal\xlsx\XlsxBatchOps::completePurgeCallback',
      'operations' => [],
    ];
    if ($entity_ids = $this->loadEntitiesByMapping()) {
      foreach (array_chunk($entity_ids, 100) as $ids) {
        $batch['operations'][] = ['\Drupal\xlsx\XlsxBatchOps::purge', [$ids]];
      }
    }
    batch_set($batch);
    $this->getLogger('xlsx')->notice(t('All previously imported data purged.'));
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() : string {
    return 'xlsx_purge_all_data_form';
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
    return t('Are you sure you would like to purge ALL previously imported data?');
  }

  /**
   * Load entity by entity type and mapping ID.
   */
  protected function loadEntitiesByMapping() {
    $result = \Drupal::entityQuery('xlsx_data')->execute();
    if ($ids = array_values($result)) {
      return $ids;
    }
  }

}
