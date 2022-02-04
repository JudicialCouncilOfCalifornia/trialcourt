<?php

namespace Drupal\xlsx\Form\Mapping;

use Drupal\Core\Form\FormStateInterface;

/**
 * Class SourceForm.
 *
 * @ingroup xlsx
 */
class SourceForm extends BaseForm {

  /**
   * {@inheritdoc}.
   */
  public function getFormId() {
    return 'xlsx_source_mapping_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $xlsx_source = NULL) {
    if (empty($this->store->get('name')) || $this->store->get('source') != $xlsx_source->getPluginId()) {
      // Make sure we don't access this page directly by skipping initial step.
      return $this->redirecToListing(); 
    }
    $this->deleteStoreByFormIds(['xlsx_entity_mapping_form', 'xlsx_fields_mapping_form', 'xlsx_review_mapping_form']);
    $form_state->setStorage(['xlsx_source' => $xlsx_source]);
    $form = parent::buildForm($form, $form_state);
    $source_form = $xlsx_source->sourceForm($form, $form_state);
    if ($source_form !== $form) {
      $form = array_merge($form, $source_form);
      $form['actions']['submit']['#value'] = $this->t('Next');
    }
    else {
      $form['missing-plugin']['#markup'] = $this->t('Plugin %name does not have upload form.', ['%name' => $xlsx_source->getName()]);
      unset($form['actions']['submit']);
    }
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $storage = $form_state->getStorage();
    $plugin_id = $this->pluginToUrlParam($storage['xlsx_source']->getPluginId());
    if ($storage['xlsx_source']->submitSourceForm($form, $form_state)) {
      $this->store->set('source_uploaded', TRUE);
      $form_state->setRedirect('xlsx.new.entity_map', ['xlsx_source' => $plugin_id]);
    }
    else {
      $form_state->setRedirect('xlsx.new.source', ['xlsx_source' => $plugin_id]);
    }
  }

}
