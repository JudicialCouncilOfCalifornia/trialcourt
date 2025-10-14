<?php

namespace Drupal\views\Plugin\views\field;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\field\BulkForm as ParentBulkForm;

/**
 * Core override: BulkForm with null-entity guard.
 */
class BulkFormOverride extends ParentBulkForm {

  /**
   * {@inheritdoc}
   */
  public function viewsForm(&$form, FormStateInterface $form_state) {
    \Drupal::logger('jcc_custom')->notice('Custom BulkForm (autoload override) invoked.');

    // Prevent caching.
    $form['#cache']['max-age'] = 0;
    $form['#attached']['library'][] = 'core/drupal.tableselect';
    $use_revision = array_key_exists('revision', $this->view->getQuery()->getEntityTableInfo());

    if (!empty($this->view->result)) {
      $form[$this->options['id']]['#tree'] = TRUE;
      foreach ($this->view->result as $row_index => $row) {
        $entity = $this->getEntity($row);
        if (!$entity instanceof EntityInterface) {
          continue;
        }
        $entity = $this->getEntityTranslation($entity, $row);
        $form[$this->options['id']][$row_index] = [
          '#type' => 'checkbox',
          '#title' => $this->t('Update this item'),
          '#title_display' => 'invisible',
          '#default_value' => !empty($form_state->getValue($this->options['id'])[$row_index]) ? 1 : NULL,
          '#return_value' => $this->calculateEntityBulkFormKey($entity, $use_revision),
        ];
      }

      $form['actions']['submit']['#value'] = $this->t('Apply to selected items');
      $form['header'] = ['#type' => 'container', '#weight' => -100];
      $form['header'][$this->options['id']] = ['#type' => 'container'];
      $form['header'][$this->options['id']]['action'] = [
        '#type' => 'select',
        '#title' => $this->options['action_title'],
        '#options' => $this->getBulkOptions(),
      ];
      $form['header'][$this->options['id']]['actions'] = $form['actions'];
    }
    else {
      unset($form['actions']);
    }
  }

}
