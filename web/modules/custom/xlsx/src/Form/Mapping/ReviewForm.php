<?php

namespace Drupal\xlsx\Form\Mapping;

use Drupal\Core\Form\FormStateInterface;

/**
 * Class ReviewForm.
 *
 * @ingroup xlsx
 */
class ReviewForm extends BaseForm {

  /**
   * {@inheritdoc}.
   */
  public function getFormId() {
    return 'xlsx_review_mapping_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    if (empty($this->store->get('source'))) {
      // Make sure we don't access this page directly by skipping initial step.
      return $this->redirecToListing();
    }
    $form = parent::buildForm($form, $form_state);
    $plugin = $this->xlsxSourceManager->createInstance($this->store->get('source'));
    $form['name'] = [
      '#type' => 'item',
      '#title' => $this->t('Name'),
      '#markup' => $this->store->get('name'),
    ];
    $form['source'] = [
      '#type' => 'item',
      '#title' => $this->t('Source'),
      '#markup' => $plugin->getName(),
    ];
    $form['source_name'] = [
      '#type' => 'item',
      '#title' => $this->t('Source Data'),
      '#markup' => $this->store->get('source_name'),
    ];
    $form['export_only'] = [
      '#type' => 'item',
      '#title' => $this->t('Export Only'),
      '#markup' => !empty($this->store->get('export_only')) ? $this->t('Yes') : $this->t('No'),
    ];
    $entity_mapping = [];
    foreach ($this->store->get('entity_mapping') as $map) {
      $worksheet = $map['worksheet'];
      $entity = $map['entity'];
      $entity_mapping[] = '<strong>' . $worksheet['name'] . '</strong> -> <strong>' . $entity['bundle'] . '</strong> (' . $entity['type'] . ')';
    }
    $form['entity_mapping'] = [
      '#type' => 'item',
      '#title' => $this->t('Entity Mapping'),
      '#markup' => join(', ', $entity_mapping),
    ];
    $export_plugins = [];
    foreach ($this->xlsxExportManager->getDefinitions() as $id => $pluginInfo) {
      $export_plugin = $this->xlsxExportManager->createInstance($pluginInfo['id']);
      if ($export_plugin->classExists()) {
        if (in_array($plugin->getPluginId(), $export_plugin->getSourceTypes())) {
          $export_plugins[$id] = $export_plugin->getName();
        }
      }
    }
    $form['export'] = [
      '#title' => $this->t('Export As'),
      '#type' => 'checkboxes',
      '#description' => $this->t('Set export file formats available for download.'),
      '#options' => $export_plugins,
      '#default_value' => $this->store->get('export') ?: [],
      '#required' => TRUE,
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->store->set('export', array_filter(array_values($form_state->getValue('export'))));
    $this->saveData();
    $form_state->setRedirect('xlsx.mapping');
  }

}
