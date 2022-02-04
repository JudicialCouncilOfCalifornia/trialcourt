<?php

namespace Drupal\xlsx\Form\Mapping;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Entity\ContentEntityType;
use Drupal\webform\Entity\Webform;

/**
 * Class FieldsForm.
 *
 * @ingroup xlsx
 */
class FieldsForm extends BaseForm {

  /**
   * {@inheritdoc}.
   */
  public function getFormId() {
    return 'xlsx_fields_mapping_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $xlsx_source = NULL) {
    $entity_mapping = $this->store->get('entity_mapping');
    if (empty($this->store->get('source_uploaded')) || empty($entity_mapping) || $this->store->get('source') != $xlsx_source->getPluginId()) {
      // Make sure we don't access this page directly by skipping initial step.
      return $this->redirecToListing();
    }
    $form_state->setStorage(['xlsx_source' => $xlsx_source]);
    $form = parent::buildForm($form, $form_state);

    $total = count($entity_mapping);
    $curr_index = (int) $this->store->get('curr_index', 0);

    $worksheet = $entity_mapping[$curr_index]['worksheet'];
    $entity = $entity_mapping[$curr_index]['entity'];
    $tmp_mapping = $this->getFieldMapping($worksheet['index']);

    $form['mapping'] = [
      '#type' => 'table',
      '#header' => [t('Destination Field'), t('Data Transformer'), t('Source Column')],
      '#prefix' => t('<h2>@worksheet (worksheet) to @bundle (@entity_type) mapping</h2>', [
        '@worksheet' => $worksheet['name'],
        '@bundle' => $entity['bundle'],
        '@entity_type' => $entity['type']
      ]),
    ];

    if ($entity['type'] == 'webform_submission') {
      $webform = Webform::load($entity['bundle']);
      $fields = $webform->getElementsInitialized();
      foreach ($fields as $field_name => $info) {
        $tmp_value = !empty($tmp_mapping[$field_name]) ? $tmp_mapping[$field_name] : [];
        $form['mapping'][$field_name]['worksheet'] = [
          '#plain_text' => $this->t('@label (@name) (@type)', [
            '@label' => $info['#title'],
            '@name' => $field_name,
            '@type' => $info['#type']
          ]),
        ];
        $form['mapping'][$field_name]['cell_plugin'] = [
          '#type' => 'select',
          '#title' => '',
          '#empty_option' => $this->t('- Select transformer -'),
          '#options' => $this->getCellPlugins($info['#type']),
          '#default_value' => !empty($tmp_value['cell_plugin']) ? $tmp_value['cell_plugin'] : 'as_is',
        ];
        $form['mapping'][$field_name]['column'] = [
          '#type' => 'select',
          '#title' => '',
          '#empty_option' => $this->t('- Select Column -'),
          '#options' => $this->getXLSXColumnsByIndex($worksheet['index']),
          '#default_value' => (isset($tmp_value['column']) && strlen(trim($tmp_value['column'])) != 0) ? $tmp_value['column'] : '',
        ];
      }
    }
    elseif (!empty($entity['type']) && !empty($entity['bundle'])) {
      $fields = $this->getFields($this->entityFieldManager->getFieldDefinitions($entity['type'], $entity['bundle']));
      foreach ($fields as $field_name => $info) {
        $tmp_value = !empty($tmp_mapping[$field_name]) ? $tmp_mapping[$field_name] : [];
        $form['mapping'][$field_name]['worksheet'] = [
          '#plain_text' => $this->t('@label (@name) (@type)', [
            '@label' => $info['label'],
            '@name' => $field_name,
            '@type' => $info['type']
          ]),
        ];
        $form['mapping'][$field_name]['cell_plugin'] = [
          '#type' => 'select',
          '#title' => '',
          '#empty_option' => $this->t('- Select transformer -'),
          '#options' => $this->getCellPlugins($info['type']),
          '#default_value' => !empty($tmp_value['cell_plugin']) ? $tmp_value['cell_plugin'] : 'as_is',
        ];
        $form['mapping'][$field_name]['column'] = [
          '#type' => 'select',
          '#title' => '',
          '#empty_option' => $this->t('- Select Column -'),
          '#options' => $this->getXLSXColumnsByIndex($worksheet['index']),
          '#default_value' => (isset($tmp_value['column']) && strlen(trim($tmp_value['column'])) != 0) ? $tmp_value['column'] : '',
        ];
      }
    }
    if ($total > 1) {
      if (($curr_index > 0 || $total === $curr_index)) {
        $form['actions']['previous'] = [
          '#type' => 'submit',
          '#value' => $this->t('Previous Worksheet'),
          '#name' => 'prev',
          '#submit' => [[$this, 'previousWorksheet']],
        ];
      }
      if ($curr_index < $total - 1) {
        $form['actions']['next'] = [
          '#type' => 'submit',
          '#value' => $this->t('Next Worksheet'),
          '#name' => 'next',
          '#submit' => [[$this, 'nextWorksheet']],
        ];
      }
    }
    if ($curr_index === $total - 1 || $total == 1) {
      $form['actions']['submit']['#value'] = $this->t('Review');
    }
    else {
      unset($form['actions']['submit']);
    }
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $mapping = $form_state->getValue('mapping');
    $selected = 0;
    foreach ($mapping as $worksheet => $info) {
      if (!empty($info['column'])) {
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
  public function previousWorksheet(array &$form, FormStateInterface $form_state) {
    $curr_index = (int) $this->store->get('curr_index', 0);
    $this->store->set('curr_index', $curr_index - 1);
    $this->setFieldMapping($curr_index, $form_state->getValue('mapping'));
  }

  /**
   * {@inheritdoc}
   */
  public function nextWorksheet(array &$form, FormStateInterface $form_state) {
    $curr_index = (int) $this->store->get('curr_index', 0);
    $this->store->set('curr_index', $curr_index + 1);
    $this->setFieldMapping($curr_index, $form_state->getValue('mapping'));
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $storage = $form_state->getStorage();
    $curr_index = (int) $this->store->get('curr_index', 0);
    $this->setFieldMapping($curr_index, $form_state->getValue('mapping'));
    $form_state->setRedirect('xlsx.new.review', [
      'xlsx_source' => $this->pluginToUrlParam($storage['xlsx_source']->getPluginId())
    ]);
  }

}
