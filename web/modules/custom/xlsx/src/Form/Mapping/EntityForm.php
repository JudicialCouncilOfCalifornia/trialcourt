<?php

namespace Drupal\xlsx\Form\Mapping;

use Drupal\Core\Form\FormStateInterface;

/**
 * Class EntityForm.
 *
 * @ingroup xlsx
 */
class EntityForm extends BaseForm {

  /**
   * {@inheritdoc}.
   */
  public function getFormId() {
    return 'xlsx_entity_mapping_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $xlsx_source = NULL) {
    if (empty($this->store->get('source_uploaded')) || $this->store->get('source') != $xlsx_source->getPluginId()) {
      // Make sure we don't access this page directly by skipping initial step.
      return $this->redirecToListing();
    }
    $form_state->setStorage(['xlsx_source' => $xlsx_source]);
    $form = parent::buildForm($form, $form_state);
    $worksheets = $xlsx_source->getWorksheets();
    $form['mapping'] = [
      '#type' => 'table',
      '#header' => [$this->t('Source Worksheet'), '', '', $this->t('Destination Entity')],
    ];

    $tmp_value = $this->store->get('entity_mapping');
    foreach ($worksheets as $index => $label) {
      $key = $index . '::' . $label;
      $form['mapping'][$key]['worksheet'] = [
        '#plain_text' => $label,
      ];
      $form['mapping'][$key]['worksheet_index'] = [
        '#type' => 'value',
        '#value' => $index,
      ];
      $form['mapping'][$key]['worksheet_name'] = [
        '#type' => 'value',
        '#value' => $label,
      ];
      $form['mapping'][$key]['entity'] = [
        '#type' => 'select',
        '#title' => '',
        '#empty_option' => $this->t('Select Entity'),
        '#options' => $this->getEntityBundles(),
        '#default_value' => !empty($tmp_value[$index]['entity']['type']) ? 
           $tmp_value[$index]['entity']['xlsx_data_plugin'] . '::' . $tmp_value[$index]['entity']['type'] . '::' . $tmp_value[$index]['entity']['bundle'] : '',
        '#disabled' => !empty($tmp_value[$index]['entity']['type']) ? TRUE : FALSE,
      ];
    }
    $form['actions']['submit']['#value'] = $this->t('Next');
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $mapping = $form_state->getValue('mapping');
    $selected = 0;
    foreach ($mapping as $worksheet => $info) {
      if (!empty($info['entity'])) {
        $selected++;
      }
    }
    if (empty($selected)) {
      $form_state->setErrorByName('mapping', $this->t('At least one mapping required to continue.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $storage = $form_state->getStorage();
    $mapping = $form_state->getValue('mapping');
    $entity_mapping = [];
    foreach ($mapping as $worksheet => $info) {
      if (!empty($info['entity'])) {
        $e = explode('::', $info['entity']);
        $entity_mapping[] = [
          'worksheet' => [
            'index' => $info['worksheet_index'],
            'name' => $info['worksheet_name']
          ],
          'entity' => [
            'xlsx_data_plugin' => $e[0],
            'type' => $e[1],
            'bundle' => $e[2]
          ],
        ];
      }
    }
    // Make sure we reset current index for Worksheet pagination.
    $this->store->set('curr_index', 0);
    $this->store->set('entity_mapping', $entity_mapping);
    $form_state->setRedirect('xlsx.new.field_map', [
      'xlsx_source' => $this->pluginToUrlParam($storage['xlsx_source']->getPluginId())
    ]);
  }

}
